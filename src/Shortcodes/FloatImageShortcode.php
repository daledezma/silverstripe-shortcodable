<?php

namespace Northwestern\Now;

use SilverStripe\Forms\LabelField;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\ViewableData;

class FloatImageShortcode extends ViewableData
{
    private static $shortcode = 'floatimg';
    private static $singular_name = 'Float Image';
    private static $plural_name = 'Float Image';
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
        if (!array_key_exists('src', $arguments) || !$arguments['src']) {
            return 'Float Image: Image path is required';
        }
        $data = new ArrayData(array(
            'Content' => $content,
            'Align' => (array_key_exists('align', $arguments)) ? $arguments['align'] : false,
            'Type' => (array_key_exists('type', $arguments)) ? $arguments['type'] : false,
            'Src' => (array_key_exists('src', $arguments)) ? $arguments['src'] : false,
            'Alt' => (array_key_exists('alt', $arguments)) ? $arguments['alt'] : false,
            'Credit' => (array_key_exists('credit', $arguments)) ? $arguments['credit'] : false,
        ));
        if (str_contains($_SERVER["QUERY_STRING"], 'fj=1') || str_starts_with($_SERVER['REQUEST_URI'], '/for-journalists')) {
            return $content;
        }
        // render with template
        return $data->renderWith('Northwestern\Now\Shortcodes\FloatImageShortcode');
    }

    /**
     * Returns a list of fields for editing the shortcode's attributes
     * in the insert shortcode popup window.
     *
     * @return Fieldlist
     **/
    public function getShortcodeFields()
    {
        // Align (left/right/headshot), Type (landscape/portrait), Credit, Caption
        $fields = FieldList::create(
            LabelField::create('intro', 'Select caption text before creating shortcode or enter it between [floatimg] tags after insertion.'),
            OptionsetField::create('align', 'Alignment', [
                    'left' => 'Left',
                    'right' => 'Right',
                    'headshot' => 'Headshot',
                ], 'left'
            ),
            OptionsetField::create('type', 'Type', [
                    'portrait' => 'Portrait',
                    'landscape' => 'Landscape',
                    'headshot' => 'Headshot',
                ], 'portrait'
            ),
            TextField::create('src', 'Image Path')
                ->setDescription('Enter path to image asset (example: assets/Images/2021/name.jpg)'),
            TextField::create('alt', 'Alt Text')
                ->setDescription('Text describing the selected image'),
            TextField::create('credit', 'Credit')
                ->setDescription('Will appear after label "Photo credit"'),
        );

        return $fields;
    }

    /**
     * Redirect to an image OR return image data directly to be displayed as shortcode placeholder in the editor
     * (getShortcodePlaceHolder gets loaded as/from the 'src' attribute of an <img> tag)
     *
     * @param array $attributes attribute key-value pairs of the shortcode
     * @return \SilverStripe\Control\HTTPResponse
     **/
    /*
    public function getShortcodePlaceHolder($attributes)
    {
        // Flavour two: output image/svg data directly (any bitmap but may also be SVG)
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
          <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
          <path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
        </svg>';

        $this->getResponse()
            ->addHeader('Content-Type', 'image/svg+xml')
            ->setBody($svg)
            ->output();
    }
    */
}
