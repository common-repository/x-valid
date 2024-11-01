<?php
/*
Plugin Name: X-Valid
Plugin URI: http://jamietalbot.com/wp-hacks/
Description: Attempts to create valid XHTML from arbitrary source.<br/>Licensed under the <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>, Copyright &copy; 2004-2006 Jamie Talbot.
Version: 1.0
Author: Jamie Talbot
Author URI: http://jamietalbot.com/
*/

/*
X-Valid - Attempt to create valid XHTML from arbitrary source.
Copyright (c) 2004-2006 Jamie Talbot
Thanks to Andy Skelton for many significant updates and improvements.

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the
Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software,
and to permit persons to whom the Software is furnished to
do so, subject to the following conditions:

The above copyright notice and this permission notice shall
be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

define ("XVALID_DIR", "wp-content/plugins/xvalid/");
define ("XVALID_DOMAIN", "xvalid");

class XValidator
{
    var $working, $tags, $unfixed, $el, $att, $commatt, $version, $options, $email_text, $message_text;
    var $dir = XVALID_DIR;
    var $bools = array(
        'inv_tags' => false,
        'inv_comm_tags' => true,
        'auto_add_atts' => false,
        'auto_remove_atts' => false,
        'remove_empty' => true,
        'auto_wrap_lists' => true,
        'show_output' => false,
        'email_author' => true,
        'parse_comments' => true,
        'convert_less_thans' => true,
        'update_check' => false,
    );

    function XValidator()
    {
        $this->version = "1.0";
        $this->tags = array();
        $this->unfixed = array();
        $this->options = array();
        $this->working = false;
        $this->emails = array();
        $this->message_text = '';

        // Set up the user interfaces
        add_action('edit_form_advanced', array(&$this, 'admin_widget'));
        add_action('edit_page_form', array(&$this, 'admin_widget'));
        add_action('admin_menu', array(&$this, 'setup'));
        add_action('admin_footer', array(&$this, 'admin_footer'));

        $this->build_doctype_list();
        $this->get_options();

        if ( isset($_GET['xvalid']) && $_GET['xvalid'] == 'show_message' )
            $this->show_message();

        // If user opts out or selected doctype is missing, leave the default filters in place.
        if ( isset($_POST['xv_nodocs']) || isset($_POST['xv_nocheck']) || (!$this->doctypes) || !array_key_exists($this->options['doctype'], $this->doctypes))
            return;

        remove_filter('comment-text', 'balanceTags');
        remove_filter('edit_post', 'balanceTags');
        remove_filter('publish_post', 'balanceTags');

        add_filter('content_save_pre', array(&$this, 'filter_post'), 8);
        add_filter('pre_comment_content', array(&$this, 'filter_comment'), 8);

        if ( isset($_POST['xv_verbose']) && $_POST['xv_verbose'] )
            add_action('save_post', array(&$this, 'verbose_post'), 8);

        add_action('comment_post', array(&$this, 'verbose_comment'), 8);
        add_action('edit_comment', array(&$this, 'verbose_comment'), 8);

        add_action('shutdown', array(&$this, 'shutdown'), 8);

				load_plugin_textdomain(XVALID_DOMAIN, XVALID_DIR);
    }

    function reset()
    {
        $this->unfixed = array();
    }

    function build_doctype_list()
    {
        if($handle = opendir(ABSPATH . $this->dir))
        {
            // Loop through all files
            while(false !== ($file = readdir($handle)))
            {
                // Build an array of php files containing a doctype header.
                if(preg_match("/\.php/", $file) && ($file != 'xvalid.php'))
                {
                    $dt = implode('', file(ABSPATH . $this->dir . "$file"));
                    if (preg_match("|Doctype:(.*)|i", $dt, $temp)) $this->doctypes[substr($file, 0, -4)] = trim($temp[1]);
                }
            }
        }
        $this->doctype_count = count($this->doctypes);
    }

    function initialise_tags($doctype = "xhtml-strict-10")
    {
        return require_once (ABSPATH . $this->dir . "$doctype.php");
    }

    function get_options()
    {
        $this->options = get_option('xvalid_options');

        if ( empty($this->options) )
            $this->reset_options();
    }

    function reset_options()
    {
        foreach ( $this->bools as $name => $default )
            $this->options[$name] = $default;

        $this->options['doctype'] = 'xhtml-transitional-10';

        $this->save_options();
    }

    function set_options()
    {
        foreach ( $this->bools as $name => $default )
            $this->options[$name] = $_POST[$name] ? true : false;

        $this->options['doctype'] = $_POST['doctype'];

        $this->save_options();
    }

    function save_options()
    {
        update_option('xvalid_options', $this->options);
    }

    function is_empty_attribute($tag, $att)
    {
        return in_array("@" . $att, $this->att[$tag]);
    }

    function is_valid_attribute($tag, $att)
    {
        $dep = "*" . $att;
        $req = "+" . $att;
        $emp = "@" . $att;
        if (!in_array($att, $this->att[$tag]) && !in_array($req, $this->att[$tag]) && !in_array($emp, $this->att[$tag]) && !in_array($dep, $this->att[$tag]))
        {
            if (in_array("core", $this->att[$tag]) && (in_array($att, $this->att['core']) || in_array($req, $this->att['core']) || in_array($dep, $this->att['core']) || in_array($emp, $this->att['core']))) return true;
            if (in_array("lang", $this->att[$tag]) && (in_array($att, $this->att['lang']) || in_array($req, $this->att['lang']) || in_array($dep, $this->att['lang']) || in_array($emp, $this->att['lang']))) return true;
            if (in_array("keyb", $this->att[$tag]) && (in_array($att, $this->att['keyb']) || in_array($req, $this->att['keyb']) || in_array($dep, $this->att['keyb']) || in_array($emp, $this->att['keyb']))) return true;
            if (in_array("window", $this->att[$tag]) && (in_array($att, $this->att['window']) || in_array($req, $this->att['window']) || in_array($dep, $this->att['window']) || in_array($emp, $this->att['window']))) return true;
            if (in_array("formevts", $this->att[$tag]) && (in_array($att, $this->att['formevts']) || in_array($req, $this->att['formevts']) || in_array($dep, $this->att['formevts']) || in_array($emp, $this->att['formevts']))) return true;
            if (in_array("events", $this->att[$tag]) && (in_array($att, $this->att['events']) || in_array($req, $this->att['events']) || in_array($dep, $this->att['events']) || in_array($emp, $this->att['events']))) return true;
            if (in_array("standard", $this->att[$tag]))
            {
                if (in_array($att, $this->att['core']) || in_array($req, $this->att['core']) || in_array($dep, $this->att['core']) || in_array($emp, $this->att['core'])) return true;
                if (in_array($att, $this->att['lang']) || in_array($req, $this->att['lang']) || in_array($dep, $this->att['lang']) || in_array($emp, $this->att['lang'])) return true;
                if (in_array($att, $this->att['keyb']) || in_array($req, $this->att['keyb']) || in_array($dep, $this->att['keyb']) || in_array($emp, $this->att['keyb'])) return true;
            }
        }
        else return true;
        return false;
    }

    function check_attributes($tag, $attributes)
    {
        $parsed = array();
        $attributes = trim($attributes);
        $original = $attributes;

        if (($this->working == 'comment') && !in_array($tag, $this->el['comments']))
        {
            if (!$this->options['inv_comm_tags']) $this->unfixed[] = "&lt;$tag&gt; is not a valid tag inside a comment, so I'm not altering its attributes.";
            return "<$tag $original>";
        }

        if ((!isset($this->att[$tag])) && (!in_array($tag, $this->el['empty'])))
        {
            if (!$this->options['inv_tags']) $this->unfixed[] = "&lt;$tag&gt; is not a valid XHTML tag, so I'm not altering its attributes.";
            return "<$tag $original>";
        }

        if ("/" == $attributes[strlen($attributes) - 1])
        {
            $attributes = substr($attributes, 0, -1);
            $e = true;
        }
        else $e = false;

        $mode = 'att';
        while (true)
        {
            // Trim the attribute string and finish if there's nothing left.
            if ("" == ($attributes = trim($attributes)))
            {
                if (('eq' == $mode) || ('val' == $mode))
                {
                    // We were looking for an equals but we reached the end of the string.
                    // If the last attribute is a valid empty attribute store it, otherwise ignore it.
                    if ($this->is_empty_attribute($tag, $att)) $parsed[$att] = "$att";
                    elseif ($this->is_valid_attribute($tag, $att)) $parsed[$att] = "";
                    elseif (!$this->options['auto_remove_atts'])
                    {
                        // Don't remove it unless we are automatically removing attributes.
                        $this->unfixed[] = sprintf(__("&lt;%s&gt; doesn't allow an empty '%s' attribute.", XVALID_DOMAIN), $tag, $att);
                        $parsed[$att] = "$att";
                    }
                }
                break;
            }

            switch($mode)
            {
                // Looking for an attribute now.
                case 'att':

                    // Whitespace marks the end of the next tag attribute.
                    if (false === ($whitespace = strpos($attributes, " "))) $whitespace = strlen($attributes);
                    if (false === ($equals = strpos(substr($attributes, 0, $whitespace), "="))) $equals = $whitespace;

                    $att = trim(substr($attributes, 0, $equals));
                    $attributes = substr($attributes, $equals);
                    $mode = 'eq';
                    continue(2);

                // Looking for an equals sign.
                case 'eq':

                    if ("=" == $attributes[0])
                    {
                        // All is good, found an equals sign next as expected.  Next, look for a value.
                        $mode = 'val';
                        $attributes = substr($attributes, 1);
                        continue(2);
                    }
                    else
                    {
                        // There was no equals sign.  If the previous keyword isn't an empty attribute ignore it.
                        if ($this->is_empty_attribute($tag, $att)) $parsed[$att] = "$att";
                        elseif ($this->is_valid_attribute($tag, $att)) $parsed[$att] = "";
                        elseif (!$this->options['auto_remove_atts'])
                        {
                            // Don't remove it unless we are automatically removing attributes.
		                        $this->unfixed[] = sprintf(__("&lt;%s&gt; doesn't allow an empty '%s' attribute.", XVALID_DOMAIN), $tag, $att);
                            $parsed[$att] = "$att";
                        }
                        // Now, look for the next attribute.
                        $mode = 'att';
                        continue(2);
                    }

                case 'val':

                    if (("'" == $attributes[0]) || ('"' == $attributes[0]))
                    {
                        $mode = 'val';
                        $attributes = substr($attributes, 1);
                        continue(2);
                    }

                    if (false === ($equals = strpos($attributes, "=")))
                    {
                        // There are no equals signs in the rest of the string.
                        // Loop backwards for the last word that is not an empty attribute.
                        if (false === ($token_start = strrpos($attributes, " ")))
                        {
                            $token_start = strlen($attributes);
                            $word = rtrim(substr($attributes, 0, $token_start));
                        }
                        else
                        {
                            $token_intermediate = $token_start + 1;
                            $word = rtrim(substr($attributes, $token_intermediate));
                            $token_start = strlen($attributes);
                        }
                    }
                    else
                    {
                        // There is an equals sign, so loop backwards from the penultimate word before = for the last
                        // non-empty attribute word.
                        if (false === ($whitespace = strrpos(rtrim(substr($attributes, 0, $equals - 1)), " ")))
                        {
                            // An attribute with a blank value can also look like this.
                            // First check that the value doesn't look like keyword=something.
                            $val = substr($attributes, 0, $equals);
                            if ($this->is_valid_attribute($tag, $val))
                            {
                                // This just looks like a value, but it's actually a new attribute after an empty one.
                                $parsed[$att] = "";
				                        $this->unfixed[] = sprintf(__("X-Valid interpreted the '%s' attribute of the &lt;%s&gt; as being empty, followed by a '%s' attribute.", XVALID_DOMAIN), $att, $tag, $val);
                                $mode = 'att';
                                continue(2);
                            }

                            // There are no spaces before the equals sign, so it's probably an href or similar.
                            // Look for the next valid equals sign.
                            while (false !== $equals)
                            {
                                $equals = strpos($attributes, "=", $equals + 1);
                                if (false !== ($token_start = strrpos(rtrim(substr($attributes, 0, $equals - 1)), " "))) break;
                            }
                            // This is the last attribute value in the string, with lots of equals signs.
                            if (false === $token_start) $token_start = strlen($attributes);
                        }
                        else
                        {
                            // $whitespace holds the point which is just before the word before =.
                            if (false === ($token_start = (strrpos(rtrim(substr($attributes, 0, $equals)), " ")))) $token_start = $whitespace;
                            else $token_start++;
                        }
                        $word = rtrim(substr($attributes, 0, $token_start));
                        $token_intermediate = $token_start;
                    }
                    if ("'" == $word[strlen($word) - 1] || '"' == $word[strlen($word) - 1]) $word = substr($word, 0, -1);
                    if (false !== strrpos($word, " ")) $word = substr($word, strrpos($word, " ") + 1);

                    while ($this->is_empty_attribute($tag, $word))
                    {
                        $token_start = $token_intermediate;
                        $token_finish = $token_intermediate - 1;

                        if (false === ($token_intermediate = strrpos(rtrim(substr($attributes, 0, $token_finish)), " "))) $token_intermediate = 0;
                        else $token_intermediate++;

                        $word = substr($attributes, $token_intermediate, $token_finish - $token_intermediate);
                        if ((" " == $word) || ("" == $word))
                        {
                            // By coincidence, all of the words are valid empty keywords.
                            // Previous tag was almost certainly an empty one, but throw a caveat just in case.
                            $parsed[$att] = "";

                            // Whitespace marks the end of the next attribute value.
                            $whitespace = strpos($attributes, " ");

                            // val holds the current attribute.
                            $val = trim(substr($attributes, 0, $whitespace));
                            if ("'" == $val[strlen($val) - 1] || '"' == $val[strlen($val) - 1]) $val = substr($val, 0, -1);
				                    $this->unfixed[] = sprintf(__("X-Valid interpreted the '%s' attribute of the &lt;%s&gt; as being empty, followed by an empty '%s' attribute.", XVALID_DOMAIN), $att, $tag, $val);

                            // Look for another attribute (even though we know they're all empty from now on).
                            $mode = 'att';
                            continue(3);
                        }
                    }

                    // $token_start now holds the point at which empty attributes begin, so close the value here.
                    $val = rtrim(substr($attributes, 0, $token_start));
                    if ("'" == $val[strlen($val) - 1] || '"' == $val[strlen($val) - 1]) $val = substr($val, 0, -1);
                    $parsed[$att] = $val;

                    // Move the parse token past the last word and look for another attribute.
                    $attributes = substr($attributes, $token_start);
                    $mode = 'att';
                    continue(2);

            }
        }

        // Do special entity replacements and remove empty atts. (Stolen from WP's own!)
        // Also lowercase all the attributes.
        foreach ($parsed as $a => $v)
        {
	          $a = strtolower($a);
            if (!$a) unset($parsed[$a]);
            else
            {
                $v = preg_replace('/&([^#])(?![a-z12]{1,8};)/', '&#038;$1', $v);
                $v = str_replace("'", '&#039;', $v);
                $parsed[$a] = str_replace('"', "&quot;", $v);
            }
        }

        // We now hopefully have a string parsed into attributes + values.

        // First, check that all required tags are there...
        foreach ($this->att[$tag] as $required)
        {
            if ("+" != $required[0]) continue;
            $required = substr($required, 1);

            if (!isset($parsed[$required]))
            {
                if (!$this->options['auto_add_atts']) $this->unfixed[] = sprintf(__("&lt;%s&gt; requires a '%s' attribute, but you have not included one.", XVALID_DOMAIN), $tag, $required);
                else $parsed[$required] = "";
            }
        }

        // Now check that all the attributes are valid in a comment, if necessary.
        if ($this->working == 'comment')
        {
            foreach ($parsed as $att => $val)
            {
                if (in_array($att, $this->commatt[$tag])) continue;

                if (!$this->options['inv_comm_tags']) $this->unfixed[] = sprintf(__("&lt;%s&gt; is not a valid attribute for a &lt;%s&gt; tag in a comment.", XVALID_DOMAIN), $att, $tag);
								else unset($parsed[$att]);
            }
        }

        // Now check that all the specified attributes are allowed in the given tag.
        foreach ($parsed as $att => $val)
        {
            if (!$this->is_valid_attribute($tag, $att))
            {
                if (!$this->options['auto_remove_atts']) $this->unfixed[] = sprintf(__("&lt;%s&gt; doesn't allow a '%s' attribute.", XVALID_DOMAIN), $tag, $att);
                else unset($parsed[$att]);
            }
        }

        $output = "<$tag";
        foreach ($parsed as $att => $val) $output .= " $att=\"$val\"";
        if ($e) $output .= " /";
        $output .= ">";

        return $output;
    }

    function check_validity($newtag)
    {
        if (!isset($this->el[$newtag]) && !in_array($newtag, $this->el['empty']))
        {
            if (!$this->options['inv_tags'])
            {
                $this->unfixed[] = sprintf(__("&lt;%s&gt; is not a valid XHTML tag, but I didn't remove it because it might be a typo!", XVALID_DOMAIN), $newtag);
                return -1;
            }
            else return -5;
        }

        $x = count($this->tags);
        $rv = -1;
        $valid = false;

        while (!$valid)
        {
            if ($x == 0) break;
            $tag = $this->tags[$x - 1];

            // Don't check against an invalid tag.
            if (isset($this->el[$tag]))
            {
                // Check for specific exclusions.
                if (!in_array("-$newtag", $this->el[$tag]))
                {
                    // Check for specific allowed.
                    if (!(in_array($newtag, $this->el[$tag])))
                    {
                        if ($newtag == $tag && -1 != $rv)
                        {
                            $rv--;
                            $valid = true;
                        }

                        // Check for inline.
                        if (!$valid && (in_array("inline", $this->el[$tag]) || in_array("flow", $this->el[$tag])))
                        {
                            if (in_array($newtag, $this->el['inline']) === false) $valid = false;
                            else $valid = true;
                        }

                        // Check for block.
                        if (!$valid && (in_array("block", $this->el[$tag]) || in_array("flow", $this->el[$tag])))
                        {
                            if (in_array($newtag, $this->el['block']) === false) $valid = false;
                            else $valid = true;
                        }
                    }
                    else $valid = true;
                }
                else $valid = false;
            }
            else
            {
                // Don't check against invalid tags.
                $x--;
                continue;
            }
            if ($valid) break;
            $rv = $x;
            $x--;
        }

        if ($rv != -1)
        {
            if (in_array($newtag, $this->el['empty']))
            {
                if (!$this->options['inv_tags']) return -5;
                else $this->unfixed[] = sprintf(__("The empty tag &lt;%s&gt; cannot be inside a &lt;%s&gt; tag.", XVALID_DOMAIN), $newtag, $this->tags[$x]);
            }
            return $rv;
        }

        // Tag is valid for the most recent child.  So, check for specific exclusions up the tree.
        $x--;
        for ($x; $x > 0; $x--)
        {
            $tag = $this->tags[$x - 1];

            // Check for specific exclusions.
            if (isset($this->el[$tag]) && in_array("-$newtag", $this->el[$tag]))
            {
                if (in_array($newtag, $this->el['empty'])) $this->unfixed[] = sprintf(__("The empty tag &lt;%s&gt; cannot be inside a &lt;%s&gt; tag.", XVALID_DOMAIN), $newtag, $this->tags[$x - 1]);
                return $x;
            }
        }
        return -1;
    }

    function print_tree($input)
    {
        $indent = 0;

        // Compress whitespace.
        $input = str_replace(array("\n", "\r", "\t"), " ", $input);
        while (true)
        {
            // Copy all text up to the beginning of the next tag.
            if (false === ($start = strpos($input, '<'))) break;
            if (false === ($end = strpos($input, '>'))) break;
            $less_than = strrpos(substr($input, 0, $end), '<');

            if (($less_than !== false) && ($less_than != $start)) $temp = substr($input, 0, $less_than);
            else $temp = substr($input, 0, $start);

            if ($temp)
            {
                $length = strlen($temp);
                while (strlen($temp) > (60 - (2 * ($indent + 1))))
                {
                    if (false === ($breaker = strrpos(substr($temp, 0, (60 - (2 * ($indent + 1)))), " ")))
                    {
                        $broken = true;
                        $breaker = (60 - (2 * ($indent + 1)));
                    }
                    $output .= "\n" . str_repeat("  ", $indent + 1) . substr($temp, 0, $breaker);
                    if ($broken)
                    {
                        $temp = "-" . substr($temp, $breaker);
                        $broken = false;
                    }
                    else $temp = substr($temp, $breaker + 1);
                }
                $output .= "\n" . str_repeat("  ", $indent + 1) . $temp;
                $input = substr($input, $length);
            }

            // Go to the beginning of the next tag
            if (false === ($start = strpos($input, '<'))) break;
            if (false === ($end = strpos($input, '>'))) break;

            if (" " == $input[1]) $output .= substr($input, 0, $end + 1);
            elseif ("!" == $input[1]) $output .= "\n" . str_repeat(" ", (60 - $end) / 2) . substr($input, 0, $end + 1);
            elseif ("/" == $input[1])
            {
                $output .= "\n" . str_repeat("  ", $indent) . substr($input, 0, $end + 1);
                if (--$indent < 0) $indent = 0;
            }
            else
            {
                $indent++;
                $output .= "\n" . str_repeat("  ", $indent) . substr($input, 0, $end + 1);
            }

            if ("/" == $input[$end - 1]) $indent--;
            $input = substr($input, $end + 1);
        }

        while (strlen($input) > (60 - (2 * ($indent + 1))))
        {
            if (false === ($breaker = strrpos(substr($input, 0, (60 - (2 * ($indent + 1)))), " ")))
            {
                $broken = true;
                $breaker = (60 - (2 * ($indent + 1)));
            }
            $output .= "\n" . str_repeat("  ", $indent + 1) . substr($input, 0, $breaker);
            if ($broken)
            {
                $input = "-" . substr($input, $breaker);
                $broken = false;
            }
            else $input = substr($input, $breaker + 1);
        }
        $output .= "\n" . str_repeat("  ", $indent + 1) . $input;

        // Try to save the situation if the text has unbalanced quotes.
        if (1 == (substr_count($output, "\"") % 2)) $output .= __("\"\n</xmp>The input text had unbalanced quotes, so the above layout is\nalmost certainly borked.  Sorry, can't do much about that.\nThis last double quote was added by the printer in an attempt\nto rescue the situation so that at least the rest of the\nfeedback will look ok.<xmp>", XVALID_DOMAIN);

        return $output;
    }

    function remove_empty_tags($unparsed)
    {
        $changes = 0;
        $output = "";
        $current = "";
        $buffer = "";

        while (true)
        {
            $i++;
            if ($i > 1000) break;

            if (!$buffer) $output .= $current;
            else
            {
                $buffer_tag = substr($current, 1, strpos($current,">") - 1);

                $nl = strpos($buffer_tag, 13);
                if ($nl === false) $nl = 1000;
                $sp = strpos($buffer_tag, " ");
                if ($sp === false) $sp = 999;

                if ($nl < $sp) $buffer_tag = trim(str_replace(strchr($buffer_tag, 13), "", $buffer_tag));
                else $buffer_tag = trim(str_replace(strchr($buffer_tag, " "), "", $buffer_tag));

                if (!$buffer_tag || (strpos($unparsed, "</$buffer_tag>") != 0))
                {
                    $output .= $buffer;
                    $buffer = "";
                }
                else
                {
                    $unparsed = substr($unparsed, strpos($unparsed,">") + 1);
                    $changes++;
                }
            }
            // Copy in non-tag text.
            $temp = substr($unparsed, 0, strpos($unparsed,"<"));
            if ($temp)
            {
                $output .= $temp;
                $unparsed = substr($unparsed, strlen($temp));
            }

            if (false === strpos($unparsed, '<')) break;
            if (!($current = substr($unparsed, strpos($unparsed, '<'), strpos($unparsed, '>') + 1))) break;

            $tag = substr($current, 1, strpos($current,">") - 1);

            if ((substr($tag, -1) != "/") && ("!--" != substr($tag, 0, 3)) && ("/" != substr($tag, 0, 1)) && (" " != substr($tag, 0, 1)) && (strrpos($tag, "<") === false)) $buffer = $current;
            else $buffer = "";
            $unparsed = substr($unparsed, strpos($unparsed,">") + 1);
        }
        $output .= $unparsed;
        if ($changes) $output = $this->remove_empty_tags($output);
        return $output;
    }

    function validate($unparsed)
    {
        $i = 0;
        $parsed = "";
        $current = "";
        $this->last_unparsed = $unparsed;
        $processed = false;
        $less_than = false;

        if (($this->working == 'comment') && !$this->options['parse_comments']) return $unparsed;
        $this->initialise_tags($this->options['doctype']);

        while (true)
        {
            $i++;
            if ($i > 10000) die (__('X-Valid Error: More than 10000 tags, probably infinite loop.', XVALID_DOMAIN));

            // Copy in the last tag parsed if it wasn't previously processed.
            if (!$processed) $parsed .= $current;
            else $processed = false;

            // Copy all text up to the beginning of the next tag.
            $temp = substr($unparsed, 0, strpos($unparsed,"<"));

            if ($temp)
            {
                $length = strlen($temp);
                $temp = str_replace(">", "&gt;", $temp);
                if ($less_than)
                {
                    if (" " != $temp[0]) $parsed .= " ";
                    $less_than = false;
                }

                if (($this->tags[count($this->tags) - 1] == "ul") || ($this->tags[count($this->tags) - 1] == "ol"))
                {
                    if ("" != trim($temp))
                    {
                        // Can't have naked text in a list.
                        if ($this->options['auto_wrap_lists'])
                        {
                            $parsed .= "<li>";
                            array_push($this->tags, "li");
                        }
                        else $this->unfixed[] = __("Only list element &lt;li&gt; tags may be inside a list, but naked text has been found.", XVALID_DOMAIN);
                    }
                }

                $parsed .= $temp;
                $unparsed = substr($unparsed, $length);
            }
            elseif ($less_than) $less_than = false;

            // Go to the beginning of the next tag
            if (false === strpos($unparsed, '<')) break;
            if (!($current = substr($unparsed, strpos($unparsed, '<'), strpos($unparsed, '>') + 1))) break;

            // Check for an unclosed tag (ie '<b' or '<li')  Also catches some less thans.
            if (strrpos($current, "<") != 0)
            {
                $current = substr($current, 0, strpos(substr($current, 1), '<') + 1);
                if (" " != $current[1])
                {
                    $current = explode("<", $current);
                    $current = implode("< ", $current);
                }

                if ($this->options['convert_less_thans']) $current = str_replace("<", "&lt;", $current);
                else $this->unfixed[] = __("A possible unclosed tag has been found.  If you want a less than sign (&lt;), you should use &amp;lt; instead.", XVALID_DOMAIN);

                if (" " != $parsed[strlen($parsed) - 1]) $parsed .= " ";
                $unparsed = substr($unparsed, strpos(substr($unparsed, 1), '<') + 1);
                continue;
            }

            // Check for a less than sign.
            if ('<' == $current)
            {
                $unparsed = substr($unparsed, 1);
                $less_than = true;

                if ($this->options['convert_less_thans']) $current = "&lt;";
                else $this->unfixed[] = __("A possible unclosed tag has been found.  If you want a less than sign (&lt;), you should use &amp;lt; instead.", XVALID_DOMAIN);

                if (" " != $parsed[strlen($parsed) - 1]) $parsed .= " ";
                continue;
            }

            // Final check for bastard less-thans or messed up tags.
            if (" " == $current[1])
            {
                if ($this->options['convert_less_thans']) $current = str_replace("<", "&lt;", $current);
                else $this->unfixed[] = __("A possible unclosed tag has been found.  If you want a less than sign (&lt;), you should use &amp;lt; instead.", XVALID_DOMAIN);

                $unparsed = substr($unparsed, strpos($unparsed, '>') + 1);
                if ($this->options['convert_less_thans']) $current = str_replace(">", "&gt;", $current);
                continue;
            }

            // Make the current tag lower case, and get attributes at the same time.
            $current = explode(" ", $current, 2);
            $current[0] = strtolower($current[0]);
            $attributes = @substr($current[1], 0, -1);
            $current = trim(implode(" ", $current));

            // Get the next tag contents.
            $tag = substr($current, 1, strpos($current,">") - 1);

            // Check to see if it is a comment.
            if ("!--" != substr($tag, 0, 3))
            {
                // First, deal with empty tags

                // Get just the first word in the tag.
                $nl = strpos($tag, 13);
                if ($nl === false) $nl = 1000;
                $sp = strpos($tag, " ");
                if ($sp === false) $sp = 999;

                if ($nl < $sp) $tag = trim(str_replace(strchr($tag, 13), "", $tag));
                else $tag = trim(str_replace(strchr($tag, " "), "", $tag));

                if (substr($tag, -1) == "/")
                {
                    // Self-closing tag without a space.
                    $tag = substr($tag, 0, strlen($tag) - 1);
                    if ("" != $attributes) $current = "<$tag $attributes>"; else $current = "<$tag>";
                }

                if ($tag[0] == "/" && in_array(substr($tag, 1), $this->el['empty']))
                {
                    if ("" == $attributes) $current = "</$tag>";
                    else
                    {
                        $tag = substr($tag, 1);
                        $current = "<$tag $attributes>";
                    }
                }

                if (in_array($tag, $this->el['empty']))
                {
                    // Close the tag if it's not been self-closed already.
                    if (substr($attributes, -1) != "/") $attributes .= " /";
                    $current = "<$tag $attributes>";
                }
                elseif (substr($attributes, -1) == "/")
                {
                    // User mistakenly closed a non-empty tag.
                    $attributes = substr($attributes, 0, strlen($attributes) - 1);

                    if ("" != $attributes) $current = "<$tag $attributes>"; else $current = "<$tag>";
                }

                // Check if it's an opening or closing.
                if ($tag[0] == "/")
                {
                    // Remove the "/";
                    $tag = substr($tag, 1);

                    // Check for an orphaned closing tag.
                    $processed = true;
                    foreach ($this->tags as $temptag)
                    {
                        if ($temptag == $tag)
                        {
                            $processed = false;
                            break;
                        }
                    }

                    if (!$processed)
                    {
                        // Pop the last entry off the stack.  Don't worry if it's none existent, as we check next...
                        $removed = @array_pop($this->tags);

                        if ($tag != $removed)
                        {
                            $parsed .= "</$removed>";

                            while ($removed = @array_pop($this->tags))
                            {
                                if ($tag == $removed) break;
                                else $parsed .= "</$removed>";
                            }
                        }
                    }
                }
                else
                {
                    // It's an opening tag.

                    // Check the attributes for the given tag, and modify if necessary.
                    if ("" != $attributes) $current = $this->check_attributes($tag, $attributes);

                    // Check it's valid for comments if necessary
                    if (($this->working == 'comment') && !in_array($tag, $this->el['comments']))
                    {
                        if (!$this->options['inv_comm_tags']) $this->unfixed[] = sprintf(__("&lt;%s&gt; is not a valid tag inside a comment.", XVALID_DOMAIN), $tag);
                        else $processed = true;
                    }

                    if (!$processed)
                    {
                        $invalid_position = $this->check_validity($tag);

                        if (-5 != $invalid_position)
                        {
                            if (-1 != $invalid_position)
                            {
                                if (!in_array($tag, $this->el['empty']))
                                {
                                    if ((($this->tags[$invalid_position - 1] == "ul") || ($this->tags[$invalid_position - 1] == "ol")) && $tag != "li")
                                    {
                                        // Special case for lists, which we can automatically wrap.
                                        if ($this->options['auto_wrap_lists'])
                                        {
                                            $parsed .= "<li>";
                                            array_push($this->tags, "li");
                                        }
                                        else $this->unfixed[] = sprintf(__("Only list element &lt;li&gt; tags may be inside a list, but a &lt;%s&gt; tag has been found.", XVALID_DOMAIN), $tag);
                                    }
                                    else
                                    {
                                        $z = 0;
                                        while (count($this->tags) >= $invalid_position)
                                        {
                                            $z++; if ($z == 25) break;

                                            if ((count($this->tags) == $invalid_position) && ($this->tags[count($this->tags) - 1] == $tag) && (!in_array($tag, $this->el['siblings']))) $processed = true;
                                            $removed = @array_pop($this->tags);
                                            if (!$processed) $parsed .= "</$removed>";
                                        }
                                    }
                                }
                            }
                            if (!in_array($tag, $this->el['empty'])) array_push($this->tags, $tag);
                        }
                        else $processed = true;
                    }
                }
            }
            // Set the parser to point to directly after the last tag.
            $unparsed = substr($unparsed, strpos($unparsed,">") + 1);
        }
        // Write in any remaining text and closing tags.
        $parsed .= $unparsed;
        while ($removed = @array_pop($this->tags)) $parsed .= "</$removed>";

        // Remove empty tags if the user wants.
        if ($this->options['remove_empty']) $parsed = $this->remove_empty_tags($parsed);

        $this->last_parsed = $parsed;

        return $parsed;
    }

    function verbose_comment($id)
    {
        if (empty($this->unfixed))
            return;

        if ($this->options['email_author'])
        {
            $commentdata = get_commentdata($id, true, true);
            $post = & get_post($commentdata['comment_post_ID']);
            $user = get_userdata($post->post_author);

            if ('' != $user->user_email)
            {
                //
                if ( !isset($this->emails[$user->user_email]) )
                    $this->emails[$user->user_email] = sprintf(__("Hi, this is X-Valid %s.  I've been processing comments posted on your site and attempting to convert them to valid XHTML.  Unfortunately, there were some mistakes I couldn't fix without deleting data or adding something I couldn't guess.  Take a look below to see what changes should be made to to ensure validity...\n\n", XVALID_DOMAIN), $this->version);

                $this->emails[$user->user_email] .= sprintf(__("Comment Details:\nComment Author: %s\nAuthor Email: %s\nAuthor URL: %s\n\n", XVALID_DOMAIN), $commentdata['comment_author'], $commentdata['comment_author_email'], $commentdata['comment_author_url']);
                $f = 1;
                foreach ($this->unfixed as $caveat)
                    $this->emails[$user->user_email] .= $f++ . ":\t$caveat\n";

                $url = get_settings('siteurl') . "/wp-admin/post.php?action=editcomment&comment={$commentdata['comment_ID']}";
                $this->emails[$user->user_email] .= sprintf(__("Edit this comment: %s\n\n", XVALID_DOMAIN), $url);
            }
        }
        $this->reset();
    }

    function verbose_post($id)
    {
        global $wp_version;
        $post = & get_post($id);

        $post_title = !empty($post->post_title) ? ", \"<em>{$post->post_title}</em>\"," : '';
        $doctype_description = $this->doctypes[$this->options['doctype']];

        if ( empty($this->message_text) )
            $this->message_text = "<html><head><title>" . sprintf(__('X-Validation Results', XVALID_DOMAIN)) . "</title><meta http-equiv=\"Content-Type\" content=\"; charset=utf-8\" /><link rel=\"stylesheet\" href=\"" . get_settings('home') . "/wp-admin/wp-admin.css\" type=\"text/css\" /></head><body>";

        $this->message_text .= '<div id="wphead"><h1>' . sprintf(__('X-Valid %s for WordPress', XVALID_DOMAIN), $this->version) . '</h1></div><div class="wrap"><p>' . sprintf(__('Hi! The post you just edited%s was checked and automatically converted to valid (hopefully) %s markup by <a href="http://jamietalbot.com/wp-hacks/xvalid/">X-Valid</a>.', XVALID_DOMAIN), $post_title, $doctype_description). '</p>';

        if (count($this->unfixed))
            $this->message_text .= '<p>' . __("However, there was at least one mistake that I didn't want to correct automatically, as it would involve deleting data or adding something I can't guess.  Take a look below to see what you need to alter for full compliance...", XVALID_DOMAIN) . '</p>';

        $this->message_text .= '<p>' . __("If you think there are any mistakes in the conversion, please post a message in the <a href=\"http://jamietalbot.com/wp-hacks/forum/\">forums</a>, including the both the input and output.  This feedback page does have its own limitations, so please be sure to check the post content itself before submitting.  Thanks!", XVALID_DOMAIN) . '</p>';
        $this->message_text .= '<p>' . sprintf(__("You can change X-Valid's settings <a href=\"%s/wp-admin/admin.php?page=xvalid/xvalid.php\">here</a>.", XVALID_DOMAIN), get_settings('home')) . '</p></div>';

        if (($this->options['update_check']) && ($version = file_get_contents("http://jamietalbot.com/wp-hacks/xvalid/version-" . substr($wp_version, 0, 3) . ".txt")) && ($version > $this->version)) fwrite($o, "<div class=\"wrap\"><h2>" . __('Update Available', XVALID_DOMAIN) . "</h2><p>" . sprintf(__("You are currently using X-Valid version %s.  The good news is that a newer version, %s is now available!  If you can be bothered, come and get it <a href=\"http://jamietalbot.com/wp-hacks/\" title=\"X-Valid Update Available\" target=\"_blank\">here</a>!", XVALID_DOMAIN), $this->version, $version) . "</p></div>");

        $this->message_text .= "<div class=\"wrap\"><h2>" . __('Input', XVALID_DOMAIN) . "</h2><p><xmp>" . $this->print_tree($this->last_unparsed) . "</xmp></p></div>";
        $this->message_text .= "<div class=\"wrap\"><h2>" . __('Output', XVALID_DOMAIN) . "</h2><p><xmp>" . $this->print_tree($this->last_parsed) . "</xmp></p></div>";
        $this->message_text .= "<div class=\"wrap\"><h2>" . __('Visible Output', XVALID_DOMAIN) . "</h2>$this->last_parsed</div>";

        if (count($this->unfixed))
        {
            $this->message_text .= "<div class=\"wrap\"><h2>" . __('Caveats', XVALID_DOMAIN) . "</h2><ul>";
            foreach ($this->unfixed as $caveat)
                $this->message_text .= "<li>$caveat</li>";
            $this->message_text .= "</ul></div>";
        }

        $this->reset();
    }

    function plugin_page()
    {
        if (isset($_POST['action']) && $_POST['action'] == "update")
        {
            $this->set_options();
            ?>
            <div class="updated fade"><p><?php _e('Options Updated', XVALID_DOMAIN) ?></p></div>
            <?php
        }
        elseif ((isset($_POST['action']) && $_POST['action'] == "reset"))
        {
            $this->reset_options();
            ?>
            <div class="updated"><p><?php _e('Options Reset', XVALID_DOMAIN) ?></p></div>
            <?php
        }

        ?>

        <div class="wrap">
        <h2><?php _e('X-Valid Options', XVALID_DOMAIN) ?></h2>
        <p>
				<?php _e('X-Valid has a number of options you can configure, enabling a customisable amount of correction and feedback.', XVALID_DOMAIN) ?>
        </p>
        <p>
        <?php _e("X-Valid can automatically check and remove invalid or redundant tags.  Use the checkboxes below to configure how you want it to behave in the given situations.  Remember, <strong>X-Valid validates against the doctype you select below.</strong> If you choose the XHTML 1.0 Strict Doctype and 'Automatically Remove Invalid Tags', tags that are valid against the <em>Transitional</em> doctype could be automatically deleted!", XVALID_DOMAIN) ?>
        </p>
        <p>
        <em><?php _e("Therefore, it's recommended that you leave auto-removal of unknown tags and attributes disabled if you don't know which tags are valid for your chosen doctype.", XVALID_DOMAIN) ?></em>
        <?php _e('In general the Transitional doctype is a safer choice if you are unsure.', XVALID_DOMAIN) ?>
				</p>

        <form name="xvoptionsform" method="post">
        <input type="hidden" name="action" value="update" />

      <div id="advancedstuff" class="dbx-group">
        <fieldset class="dbx-box" <?php if (!$this->doctype_count) { ?>style="background: red; border-color: black" <?php } ?>>
        <h3 class="dbx-handle"<?php if (!$this->doctype_count) { ?> style="background: red; border: 1px solid black;" <?php } ?>> <?php _e('Validating Doctype', XVALID_DOMAIN) ?></h3>
      <div class="dbx-content">
        <ul>
        <?php
        $doc_count = 0;
        if (!$this->doctype_count)
				{
					?><li style="list-style: none; text-align: center"><strong><em>
					<?php
						if (file_exists(ABSPATH . "wp-plugin-mgr.php"))
						{
							$update_path = get_settings('home') . "/wp-plugin-mgr.php";
							printf(__("X-Valid couldn't find any doctypes to validate against.  Please add valid doctype files to X-Valid's folder or reinstall the plugin using the <a href=\"%s\">Plugins Manager</a>.", XVALID_DOMAIN), $update_path);
						}
						else _e("X-Valid couldn't find any doctypes to validate against.  Please add valid doctype files to X-Valid's folder or reinstall the plugin.", XVALID_DOMAIN);
					?></em></strong></li>
					<?php
				}
        else
        {
            foreach ($this->doctypes as $internal => $doc_desc)
            {
                $doc_count++;
            ?>
            <li>
            <label for="<?php echo "doctype_$doc_count" ?>">
            <input name="doctype" type="radio" id="<?php echo "doctype_$doc_count" ?>" value="<?php echo $internal ?>" <?php if ($internal == $this->options['doctype']) echo " checked=\"checked\""; ?>/>
            <?php echo $doc_desc ?></label>
            </li>
            <?php
            }
        }
        ?>
        </ul>
        </div>
        </fieldset>

      <fieldset class="dbx-box">
      <h3 class="dbx-handle"><?php _e('Auto-Correction', XVALID_DOMAIN) ?></h3>
      <div class="dbx-content">
        <ul>
        <li>
        <label for="inv_tags">
        <input name="inv_tags" type="checkbox" id="inv_tags" value=true<?php if ($this->options['inv_tags']) echo " checked=\"checked\""; ?> />
        <?php _e('Automatically remove invalid tags (Not recommended).', XVALID_DOMAIN) ?></label>
        </li>
        <li>
        <label for="inv_comm_tags">
        <input name="inv_comm_tags" type="checkbox" id="inv_comm_tags" value=true<?php if ($this->options['inv_comm_tags']) echo " checked=\"checked\""; ?> />
        <?php _e('Automatically remove unavailable tags from post comments.', XVALID_DOMAIN) ?></label>
        </li>
        <li>
        <label for="auto_add_atts">
        <input name="auto_add_atts" type="checkbox" id="auto_add_atts" value=true<?php if ($this->options['auto_add_atts']) echo " checked=\"checked\""; ?> />
        <?php _e('Automatically add required attributes.', XVALID_DOMAIN) ?></label>
        </li>
        <li>
        <label for="auto_remove_atts">
        <input name="auto_remove_atts" type="checkbox" id="auto_remove_atts" value=true<?php if ($this->options['auto_remove_atts']) echo " checked=\"checked\""; ?> />
        <?php _e('Automatically remove invalid attributes (Not recommended).', XVALID_DOMAIN) ?></label>
        </li>
        <li>
        <label for="remove_empty">
        <input name="remove_empty" type="checkbox" id="remove_empty" value=true<?php if ($this->options['remove_empty']) echo " checked=\"checked\""; ?> />
        <?php _e('Automatically remove tag pairs with no content.', XVALID_DOMAIN) ?></label>
        </li>
        <li>
        <label for="auto_wrap_lists">
        <input name="auto_wrap_lists" type="checkbox" id="auto_wrap_lists" value=true<?php if ($this->options['auto_wrap_lists']) echo " checked=\"checked\""; ?> />
        <?php _e('Automatically wrap list elements in &lt;li&gt; tags if required.', XVALID_DOMAIN) ?></label>
        </li>
        <li>
        <label for="convert_less_thans">
        <input name="convert_less_thans" type="checkbox" id="convert_less_thans" value=true<?php if ($this->options['convert_less_thans']) echo " checked=\"checked\""; ?> />
        <?php _e('Automatically convert less thans (&lt;) to &amp;lt; entities.', XVALID_DOMAIN) ?></label>
        </li>
        </ul>
        </div>
        </fieldset>

      <fieldset class="dbx-box">
      <h3 class="dbx-handle">Feedback</h3>
      <div class="dbx-content">
        <ul>
        <li>
        <label for="show_output">
        <input name="show_output" type="checkbox" id="show_output" value=true<?php if ($this->options['show_output']) echo " checked=\"checked\""; ?> />
        <?php _e('Show parse results by default (May be overridden for individual articles).', XVALID_DOMAIN) ?></label>
        </li>
        <li>
        <label for="email_author">
        <input name="email_author" type="checkbox" id="email_author" value=true<?php if ($this->options['email_author']) echo " checked=\"checked\""; ?> />
        <?php _e('Email post author when a badly marked up comment is added to their post.', XVALID_DOMAIN) ?></label>
        </li>
        </ul>
        </div>
        </fieldset>

      <fieldset class="dbx-box">
      <h3 class="dbx-handle"><?php _e('Miscellaneous', XVALID_DOMAIN) ?></h3>
      <div class="dbx-content">
        <ul>
        <li>
        <label for="parse_comments">
        <input name="parse_comments" type="checkbox" id="parse_comments" value=true<?php if ($this->options['parse_comments']) echo " checked=\"checked\""; ?> />
        <?php _e('Parse comments.', XVALID_DOMAIN) ?></label>
        </li>
        <li>
        <label for="update_check">
        <input name="update_check" type="checkbox" id="update_check" value=true<?php if ($this->options['update_check']) echo " checked=\"checked\""; ?> />
        <?php _e('Automatically check for updates to X-Valid on this page or when feedback is generated.', XVALID_DOMAIN) ?></label>
        </li>
        </ul>
        </div>
        </fieldset>

        <fieldset class="dbx-box" style="float: left; width: 255px;">
        <h3 class="dbx-handle"><?php printf(__('X-Valid %s', XVALID_DOMAIN), $this->version) ?></h3>
      <div class="dbx-content" style="padding-left: 3px">
        <?php _e('Copyright &copy; 2004-2006, <a href="http://jamietalbot.com/">Jamie Talbot</a>', XVALID_DOMAIN) ?><br />
        <?php _e('Licensed under the <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>.', XVALID_DOMAIN) ?>
        </div>
        </fieldset>

        <?php
        if (($this->options['update_check']) && ($version = file_get_contents("http://jamietalbot.com/wp-hacks/xvalid/version-" . substr($wp_version, 0, 3) . ".txt")) && ($version > $this->version)) { ?>

        <fieldset class="dbx-box" style="float: left; margin-left: 0.5em; width: 255px;">
        <h3 class="dbx-handle"><?php _e('Update Available', XVALID_DOMAIN) ?></h3>
      <div class="dbx-content" style="padding-left: 3px">
        <?php printf(__('Version %s now available!', XVALID_DOMAIN), $version) ?><br />
				<?php
				if (file_exists(ABSPATH . "wp-plugin-mgr.php")) printf(__("Get it <a href=\"http://jamietalbot.com/wp-hacks/xvalid/\">here</a> or using the <a href=\"%s\">Plugins Manager</a>.", XVALID_DOMAIN), $update_path);
			  else _e("Get it <a href=\"http://jamietalbot.com/wp-hacks/xvalid/\">here</a>.", XVALID_DOMAIN);
				?>
      </div>
        </fieldset>
        </div>

        <?php } ?>

        <p class="submit">
        <input type="submit" name="Submit" value="<?php _e('Update Options &raquo;', XVALID_DOMAIN) ?>" />
        </p>
        </form>

        <form name="xvresetform" method="post">
        <input type="hidden" name="action" value="reset" />
        <p class="submit">
        <input type="submit" name="Defaults" id="deletepost" value="<?php _e('Reset To Defaults &raquo;', XVALID_DOMAIN) ?>" />
        </p>
        </form>

        </div>
    <?php
    }

    function filter_post($content)
    {
        $this->working = 'post';
        $content = $this->validate(stripslashes($content));
        $this->working = false;
        return addslashes($content);
    }

    function filter_comment($content)
    {
        $this->working = 'comment';
        $content = $this->validate($content);
        $this->working = false;
        return $content;
    }

    function setup()
    {
        add_submenu_page('plugins.php', 'X-Valid', 'X-Valid', 5, 'xvalid/xvalid.php', array(&$this, 'plugin_page'));
    }

    function admin_widget($ignored)
    {
        ?>
            <fieldset class="dbx-box" id="xvoptionsdiv" style="margin-top: 10px;">
            <h3 class="dbx-handle">X-Valid <?php echo $this->version ?></h3>
            <div class="dbx-content">
            <p>
            <?php if ($this->doctype_count) { ?>
            <label style="float: right;"><input type="checkbox" name="xv_nocheck" onchange="if(this.checked){document.getElementById('xv_feedback').style.display = 'none';}else{document.getElementById('xv_feedback').style.display = 'block';}" /> Don't Check This Post</label>
            <em><?php printf(__("This text will be processed against the <strong>%s</strong> doctype.</em>", XVALID_DOMAIN), $this->doctypes[$this->options['doctype']]) ?><br />
            <span style="margin: 2px 0 10px" id="xv_feedback">
                <label style="float: right;"><input type="checkbox" name="xv_verbose"<?php if ($this->options['show_output']) echo " checked=\"checked\""; ?> /> Show Results</label>
            </span>
            <?php } else { ?>
            <strong><?php _e('Warning: X-Valid could not find any doctypes to validate against.  No processing will take place.', XVALID_DOMAIN) ?></strong>
            <input type="hidden" name="xv_nodocs" value=true />
            <?php } ?>
            </p>
            </div></fieldset>
        <?php
    }

    function admin_footer()
    {
      if (isset($this->options['message_text']))
      {
				?>
        <script type="text/javascript">window.open('<?php echo get_settings('home') . "?xvalid=show_message" ?>','_blank','width=600,height=600,resizable=0,scrollbars=yes');</script>
				<?php
			}
		}

    function shutdown()
    {
        if ( count($this->emails) )
            $this->send_emails();
        if ( ! empty($this->message_text) )
            $this->save_message();
    }

    function send_emails() {
        $blogname = stripslashes(get_settings('blogname'));
        $host = parse_url(get_bloginfo('home'));
        $host = $host['host'];
        $from = "From: \"$blogname\" <wordpress@{$host}>";
        $message_headers = "MIME-Version: 1.0\r\n{$from}\r\nContent-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\r\n";

        foreach ( $this->emails as $to => $body )
            mail($to, sprintf(__("X-Valid %s Comment Report", XVALID_DOMAIN), $this->version), $body, $message_headers);
    }

    function save_message() {
        $this->message_text .=  __("<div class=\"wrap\">Copyright &copy; 2004-2006, <a href=\"http://jamietalbot.com\">Jamie Talbot</a>.<br />Licensed under the <a href=\"http://www.opensource.org/licenses/mit-license.php\">MIT License</a>.", XVALID_DOMAIN) . "</div><br /></body></html>";

        $this->options['message_text'] = $this->message_text;
        $this->save_options();
    }

    function show_message() {
        echo $this->options['message_text'];
				unset($this->options['message_text']);
        $this->save_options();
        wp_cache_close();
        exit;
    }
}

$xvalidator = new XValidator();

/*
TODO:
Ignore <code> and <pre> tags.
Better printing of results for long tags.
Move widget to post sidebar.
Roles: Admin can create without X-Valid.
Move specifications to XML.
Improve notification email.

*/

?>