<?php

namespace Northwestern\Now;

use SilverStripe\View\ArrayData;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\ViewableData;

class TextInterrupterShortcode extends ViewableData
{
    private static $shortcode = 'interrupter';
    private static $singular_name = 'Text Interrupter';
    private static $plural_name = 'Text Interrupter';
    private static $shortcode_close_parent = false;

    /**
     * @return mixed
     */
    public function singular_name()
    {
        return $this->config()->get('singular_name');
    }

    /**
     * Parse the shortcode and render as a string, probably with a template.
     *
     * @param array           $arguments the list of attributes of the shortcode
     * @param string          $content    the shortcode content
     * @param ShortcodeParser $parser     the ShortcodeParser instance
     * @param string          $shortcode  the raw shortcode being parsed
     *
     * @return string
     */
    public static function parse_shortcode($arguments, $content, $parser, $shortcode)
    {
        if ($content == '') {
            $content = 'content';
        }
        $data = new ArrayData(array(
            'Content' => $content,
        ));
        if (str_contains($_SERVER["QUERY_STRING"], 'fj=1') || str_starts_with($_SERVER['REQUEST_URI'], '/for-journalists')) {
            return $content;
        }
        // render with template
        return $data->renderWith('Northwestern\Now\Shortcodes\TextInterrupterShortcode');
    }
}
