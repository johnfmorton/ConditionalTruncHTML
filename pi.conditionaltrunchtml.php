<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
========================================================
Plugin ConditionalTruncHTML
--------------------------------------------------------
Copyright: Oliver Heine
License: Freeware
http://utilitees.de/ee.php/trunchtml
--------------------------------------------------------
This addon may be used free of charge. Should you
employ it in a commercial project of a customer or your
own I'd appreciate a small donation.
========================================================
File: pi.conditionaltrunchtml.php
--------------------------------------------------------
Purpose: Truncates HTML to the specified length without
leaving open tags. Truncation can be based on the length
of another string being passed in.
========================================================
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF
ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO
EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN
AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
OR OTHER DEALINGS IN THE SOFTWARE.
========================================================
*/


$plugin_info = array(  'pi_name' => 'ConditionalTruncHTML',
    'pi_version' => '1.0',
    'pi_author' => 'Based plug-in by Oliver Heine. Conditional modifications by John Morton',
    'pi_author_url' => 'n/a',
    'pi_description' => 'Truncates HTML/Text to the specified number of characters based on the length of another block of text. Does not count characters in HTML-tags, does not cut-off in the middle of tags, closes all open tags.',
    'pi_usage' => conditionaltrunchtml::usage());

class Conditionaltrunchtml
{
    var $return_data;

    function Conditionaltrunchtml()
    {
        $this->EE =& get_instance();

        $text = $this->EE->TMPL->tagdata;
		// the text that will be measured to determine the length of truncation
		$basedontext = $this->EE->TMPL->fetch_param('basedontext', '');
		
		// if basedontext < X | < Y | <Z
		$maxlengths = $this->EE->TMPL->fetch_param('maxlengths', '');
		$maxlengths = explode ('|', $maxlengths);
		
		// then limit to Xlimit | Ylimit | Zlimit
		$charstolimits = $this->EE->TMPL->fetch_param('charstolimits', '');
		$charstolimits = explode('|', $charstolimits);
		
		// Check that maxlength and charstolimits are of equal length
		try {
			if (count($maxlengths) != count($charstolimits)) {
				//throw new Exception("The maxlengths set and charstolimit set needs to be of equal length.");	
				$this->throwError("The maxlengths set and charstolimit set needs to be of equal length. Check the values you are setting for the exp:conditionaltrunchtml in your template. ");
			}
		} 
		
		catch (Exception $e){
			echo "ERROR in <em>exp:conditionaltrunchtml</em> : " . $e->getMessage();
		}
		
		// Check that maxlength values are in proper order, from smallest to largest
		for ($i = count($maxlengths); $i > 1; $i--) {
			//echo "trying " . $i . " : " . $maxlengths[$i-1] . " vs " . $maxlengths[$i-2] . ". <br>";
			try {
				if ($maxlengths[$i-1] < $maxlengths[$i-2]) {
					
					$this->throwError("The maxlengths set must be ordered from shortest length to longest. Found " . $maxlengths[$i-2] . ' before ' . $maxlengths[$i-1] .' in the parameters passed into function. <br><br>' );
					
				}
			}
			catch (Exception $e){
				echo "ERROR in <em>exp:conditionaltrunchtml</em> : " . $e->getMessage();
				
			}
			
		}
		
		// if a passed in string is longer than the greatest maxlength value, it will fall back to the 'defaultchars' value. 
		// if no defaultchars value is set, it will use 500 as the default value
		$chars = $this->EE->TMPL->fetch_param('defaultchars','500');
		
		$basedonlength = strlen($basedontext);
		
		for ($i = 0; $i < count($maxlengths); $i++) {
			if ($basedonlength <= $maxlengths[$i]) {
				$chars = $charstolimits[$i];
				break;
			}			
		}	
		
        $threshold = $this->EE->TMPL->fetch_param('threshold', '0');
        $ending = $this->EE->TMPL->fetch_param('ending','');
        $exact = $this->EE->TMPL->fetch_param('exact','no');
        $inline = $this->EE->TMPL->fetch_param('inline','');

        $raw = strlen(preg_replace('/<.*?>/', '', $text));

        if ( $raw <= $chars)
        {
            $this->return_data = $text;
            return;
        }

        if ( $threshold > 0 && $raw < $threshold )
        {
            $this->return_data = $text;
            return;
        }

        preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
        $total_length = 0;
        $open_tags = array();
        $truncate = '';
        foreach ($lines as $line_matchings)
        {
            if (!empty($line_matchings[1]))
            {
                if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1]))
                {
                }
                elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings))
                {
                    $pos = array_search($tag_matchings[1], $open_tags);
                    if ($pos !== false)
                    {
                        unset($open_tags[$pos]);
                    }
                }
                elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings))
                {
                    array_unshift($open_tags, strtolower($tag_matchings[1]));
                }
                $truncate .= $line_matchings[1];
            }
            $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
            if ($total_length + $content_length > $chars)
            {
                $left = $chars - $total_length;
                $entities_length = 0;
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE|PREG_PATTERN_ORDER))
                {
                    foreach ($entities[0] as $entity)
                    {
                        if ($entity[1] + 1 - $entities_length <= $left)
                        {
                            $left--;
                            $entities_length += strlen($entity[0]);
                        }
                        else
                        {
                            break;
                        }
                    }
                }
                $truncate .= substr($line_matchings[2], 0, $left + $entities_length);

                break;
            }
            else
            {
                $truncate .= $line_matchings[2];
                $total_length += $content_length;
            }
            if ($total_length >= $chars)
            {
                break;
            }
        }


        if ($exact != "yes")
        {
            $last_gt = strrpos($truncate, '>');
            $spacepos = strrpos($truncate, ' ');
            if ( ($last_gt !== FALSE && $spacepos !== FALSE) && $last_gt > $spacepos )
            {
                $spacepos = strrpos($truncate, '<');
                if ($spacepos !== FALSE)
                {
                    $truncate = substr($truncate, 0, $spacepos);
                    array_shift($open_tags);
                }
            }
            elseif ( $spacepos !== FALSE )
            {
                $truncate = substr($truncate, 0, $spacepos);
            }
        }

        $truncate = rtrim($truncate);

        if (!empty($inline))
        {
            if (substr($inline,0,1)=="_")
            {
                $inline = " ".ltrim($inline,"_");
            }
            $truncate .= $inline;
        }

        foreach ($open_tags as $tag)
        {
            $truncate .= '</' . $tag . '>';
        }

        if ( !empty($ending) )
        {
            $truncate .= $ending;
        }

        $this->return_data = $truncate;
    }
	
	function throwError($errorMessage) {
		throw new Exception($errorMessage);
	}

    // ----------------------------------------
    //  Plugin Usage
    // ----------------------------------------
    // This function describes how the plugin is used.
    //  Make sure and use output buffering
    function usage()
    {
        ob_start();
        ?>
Use:
Based on Oliver Heine's go to plug-in, TruncHTML, ConditionalTruncHTML allows you to truncate text 
based on the length of another block of text. Typically, if you have a limited amount of space
for both a headline and a body copy, you will want to limit the length of the body copy snippet
based on how long the title of the entry is. 

Example:
----------------
{exp:conditionaltrunchtml basedontext="{title}" maxlengths="5|10|15" charstolimits='100|75|50' defaultchars='150' inline="..." ending="<a href='{path=site/comments}'>read on</a>"}
{body}
{/exp:conditionaltrunchtml}

Parameters:
----------------
basedontext=""
The text to base the conditional statement on. Text will be measured for its string length.

maxlengths=""
This is a set of maximum character lengths that 'basedontext' will be evaluated against. This list
can be any set of integers, separated by a pipe character, |. The numbers must be listed from
smallest to greatest.

charstolimits=""
This is a set of character limits that correspond to the "maxlengths", separated by a pipe character, |.
The value must be an integer. The order refers to the order you set in the 'maxlengths' set. This set 
does not need to be from smallest to greatest. Typically, it will be from greatest to smallest,
if you're trying to balance out a block of text.

defaultchars=""
Defaults to 500. Number of characters that are to be returned. This default value typically ends up being
used when the "basedontext" is longer than the greatest value you enter in the "maxlengths" values.

ending=""
Optional. String to be added after the output.

inline=""
Optional. This string is placed directly _after_ the truncated
text and _before_ any closing tags.
If you want the first character to be a space, use an underscore
e.g. inline="_continue"

exact="yes"
If this is set, text will be truncated after exactly the specified
number of chars. Otherwise text will be cut after a space to prevent
cutting words in the middle.

threshold="X"
If this is set the text will only be truncated if it at least X characters long.
Otherwise the full text is returned.

----------------
CHANGELOG:

1.0
* 1st version 

        <?php
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }
      /* END */

}
/* END Class */
/* End of file pi.conditionaltrunchtml.php */
/* Location: ./system/expressionengine/third_party/conditionaltrunchtml/pi.conditionaltrunchtml.php */