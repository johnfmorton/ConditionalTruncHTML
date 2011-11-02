ConditionalTruncHTML
================================

# Why? #

This Expression Engine 2 add on was created because I needed to visually balance a number of blocks of text, each with a headline and a body copy area. There a few text truncation add ons for EE, but I needed to be able to choose the length of the truncation, for the body copy, based on the length of another chunk of text, the headline. 

To create balanced text boxes, I basically wanted a short headline to have a more text in the truncated body and a long headline to have less truncated text in the body.  

I have used Oliver Heine's TruncHTML in the past for truncation needs, but I needed some conditional logic added in, so I modified it for that purpose. Oliver Heine's TruncHTML was Freeware and so is this. I hope you find it helpful.

# Use: #
Based on Oliver Heine's go to plug-in, TruncHTML, ConditionalTruncHTML allows you to truncate text based on the length of another block of text. Typically, if you have a limited amount of space for both a headline and a body copy, you will want to limit the length of the body copy snippet based on how long the title of the entry is. 

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
This is a set of maximum character lengths that 'basedontext' will be evaluated against. This list can be any set of integers, separated by a pipe character, |. The numbers must be listed from smallest to greatest.

charstolimits=""
This is a set of character limits that correspond to the "maxlengths", separated by a pipe character, | . The value must be an integer. The order refers to the order you set in the 'maxlengths' set. This set does not need to be from smallest to greatest. Typically, it will be from greatest to smallest, if you're trying to balance out a block of text.

defaultchars=""
Defaults to 500. Number of characters that are to be returned. This default value typically ends up being used when the "basedontext" is longer than the greatest value you enter in the "maxlengths" values.

ending=""
Optional. String to be added after the output.

inline=""
Optional. This string is placed directly _after_ the truncated text and _before_ any closing tags.
If you want the first character to be a space, use an underscore e.g. inline="_continue"

exact="yes"
If this is set, text will be truncated after exactly the specified number of chars. Otherwise text will be cut after a space to prevent cutting words in the middle.

threshold="X"
If this is set the text will only be truncated if it at least X characters long. Otherwise the full text is returned.


