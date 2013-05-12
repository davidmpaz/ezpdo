<?php

/**
 * $Id: epComment.php 1013 2006-09-27 01:55:43Z nauhygon $
 *
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @author David Paz <davidmpaz@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 */
namespace ezpdo\compiler\tag;

/**
 * Class of a ezpdo comment block
 *
 * The class takes comments in source code as the input and
 * parses it into tag-value pairs. Usage:
 * <pre>
 * $c = new epComment($comment);
 * $c->getTagValue('var');
 * </pre>
 *
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision: 1013 $ $Date: 2006-09-26 21:55:43 -0400 (Tue, 26 Sep 2006) $
 * @package ezpdo
 * @subpackage ezpdo.compiler.tag
 */
class epComment {

    /**
     * The array that holds tag-values
     * @var array
     */
    protected $tag_values = array();

    /**
     * Constructor
     * @param string
     */
    public function __construct($comment) {
        $this->parse($comment);
    }

    /**
     * Check if comment has a particular tag
     * @param string tag name
     * @return bool
     */
    public function hasTag($tag_name) {
        if ($tag_name) {
            return array_key_exists($tag_name, $this->tag_values);
        }
        return false;
    }

    /**
     * Returns all tags
     * @return array (tag-value pairs)
     */
    public function getTags() {
        return $this->tag_values;
    }

    /**
     * Returns the value of a tag
     * @param string tag name
     * @return false|string false if tag not found or tag value (null if tag value not set)
     */
    public function getTagValue($tag_name) {
        if (!$this->hasTag($tag_name)) {
            return false;
        }
        return $this->tag_values[$tag_name];
    }

    /**
     * Preprocess comment (remove excessive space, comment boarder)
     * @param string the original comment
     * @return string the processed comment
     */
    private function preproc($comment) {

        // remove comment boarders
        $comment = preg_replace(

            // patterns
            array(
                "/\n/",                // save our newlines, as they're considered part of '\s' in regex
                "/\s*\/+\**\s+/i",     // /* or /** or /*** or //*.. and trailing spaces
                "/^\s*\*\**\/?\s*/im", // *'s and trailing spaces on a new line
                "/\{\s*@\w*.*\}/i",    // ignore inline tags
                "/____ezpdonl____/",   // and then put the newlines back in
                ),

            // replacement
            array(
                "____ezpdonl____",
                " ",
                " ",
                "",
                "\n"
                ),

            $comment
            );

        return $comment;
    }

    /**
     * Parse the comment into tag-value array
     * @param string
     * @return bool
     */
    private function parse($comment) {

        // check if comment is empty
        if (!$comment) {
            return false;
        }

        // preproc the comment
        $preproced = $this->preproc($comment);

        // split comments by line for processing
        $preproced = explode("\n", $preproced);

        foreach ($preproced as $line) {

            /**
             * split string into an array of tags and values. normally a
             * value follow a tag, but it's possible a tag does not have
             * a value following (ie an empty tag).
             */
            $pieces = preg_split("/(@\w+)\s+/", $line, -1, PREG_SPLIT_DELIM_CAPTURE);
            if (!$pieces) {
                return false;
            }

            // associate tags and values
            reset($pieces);
            $piece = next($pieces);
            do {

                // trim piece
                $piece = trim($piece);

                // is it a tag
                if (!$piece || !isset($piece[0]) || $piece[0] !== '@') {
                    $piece = next($pieces);
                    continue;
                }

                // process tag
                $tag = substr($piece, 1);

                // check if next piece is value
                $piece = next($pieces);

                // trim piece
                $piece = trim($piece);

                if (!$piece || $piece[0] === '@') {
                    // if the next value is a tag, no value for this tag
                    $this->tag_values[$tag] = null;
                } else {
                    $this->tag_values[$tag] = $piece;
                    $piece = next($pieces);
                }

            } while ($piece !== false);

        }

        return true;
    }

}
