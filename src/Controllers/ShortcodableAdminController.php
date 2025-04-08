<?php

namespace Shortcodable\Controllers;

use Shortcodable\Shortcodable;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Parsers\ShortcodeParser;

class ShortcodableAdminController extends Controller
{
    private static $url_segment = 'admin/shortcodable';

    /** @Config default shortcode placeholder svg-image config values */
    private static $default_placeholder = [
        'height' => 46,
        'full_height' => 460,
        'width' => 240,
        'full_width' => 1000,
        'font' => 'Consolas, "Lucida Console", "DejaVu Sans Mono", "Liberation Mono", "Nimbus Mono L", Monaco, "Courier New", Courier, monospace',
        'fontsize' => 16,
        'fg' => 'ffffff',
        'bg' => '338dc1', // dark blue as used in the CMS
    ];

    /**
     * @var array
     */
    private static $allowed_actions = [
        'index' => 'CMS_ACCESS_CMSMain',
        'shortcodePlaceHolder' => 'CMS_ACCESS_CMSMain',
    ];

    /**
     * @var array
     */
    private static $url_handlers = [
        'placeholder/$Shortcode!/$ObjectID/$OtherProp' => 'shortcodePlaceHolder'
    ];

    /**
     * @var array
     */
    protected $shortcodedata;

    /**
     * Provides content (form html) for the shortcode dialog
     **/
    public function index()
    {
        $shortcode_info = Shortcodable::shortcode_class_info();
        $shortcode = $this->request->postVar('ShortcodeType');
        $classname = isset($shortcode_info['sc_class_map'][$shortcode]) ? $shortcode_info['sc_class_map'][$shortcode] : '';
        $shortcodeLabel = isset($shortcode_info['sc_label_map'][$shortcode]) ? $shortcode_info['sc_label_map'][$shortcode] : $shortcode;

        // essential fields
        $fields = FieldList::create([
            DropdownField::create('ShortcodeType', '', $shortcode_info['sc_label_map'])
                ->setEmptyString(_t('Shortcodable.SHORTCODETYPE', 'Shortcode type'))
                ->addExtraClass('shortcode-type _form-group')
        ]);

        if ($classname && class_exists($classname)) {
            $classObj = singleton($classname);
            $fields->insertAfter('ShortcodeType', HeaderField::create('ShortCodeHeading', $shortcodeLabel));

            if (is_subclass_of($classObj, DataObject::class)) {
                if (singleton($classname)->hasMethod('getShortcodableRecords')) {
                    $dataObjectSource = singleton($classname)->getShortcodableRecords();
                } elseif (singleton($classname)->hasMethod('get_shortcodable_records')) {
                    $dataObjectSource = singleton($classname)->get_shortcodable_records();
                } else {
                    $dataObjectSource = $classname::get()->map()->toArray();
                }
                $fields->push(
                    DropdownField::create('id', $classObj->singular_name(), $dataObjectSource)
                        ->setHasEmptyDefault(true)
                );
            }

            $attrFields = null; // shortcode may not need any attributes at all
            if ($classObj->hasMethod('getShortcodeFields')) {
                $attrFields = $classObj->getShortcodeFields();
            }
            // Legacy fallback (from when shortcodable was a Trait) probably safe to remove at some point...
            elseif ($classObj->hasMethod('shortcode_attribute_fields')){
                $attrFields = $classObj::shortcode_attribute_fields();
            }
            if ($attrFields) {
                foreach($attrFields as $attrField){
                    $fields->push($attrField);
                }
            }
        }

        // actions
        $actions = FieldList::create();
        if($type = $this->request->postVar('ShortcodeType')) {
            $actions->push(
                FormAction::create('insert', _t('Shortcodable.BUTTONINSERTSHORTCODE', 'Insert shortcode'))
                    //                ->addExtraClass('btn btn-primary font-icon-tick')
                    ->addExtraClass('btn btn-primary font-icon-plus-circled')
                    ->setUseButtonTag(true)
            );
        }

        // form
        $form = Form::create($this, 'ShortcodeForm', $fields, $actions)
            ->loadDataFrom($this->request->postVars())
            ->addExtraClass('htmleditorfield-form htmleditorfield-shortcodable _cms-edit-form');

        $this->extend('updateShortcodeForm', $form);

        return $form->forTemplate();
    }

    /**
     * Generates shortcode placeholder img url to display inside TinyMCE instead of the shortcode.
     *
     * @return \SilverStripe\Control\HTTPResponse|string|void
     */
    public function shortcodePlaceHolder($request)
    {
        $sc_key = $request->param('Shortcode');
        $object_id = $request->param('ObjectID');
        $sc_class_map = Shortcodable::get_shortcodable_classes_with_placeholders();
        $sc_class = array_key_exists($sc_key, $sc_class_map) ? $sc_class_map[$sc_key] : null;
        if (!$sc_class || !class_exists($sc_class)) {
            return;
        }

        if ($object_id && is_subclass_of($sc_class, DataObject::class)) {
            $object = $sc_class::get()->byID($object_id);
        } else {
            $object = singleton($sc_class);
        }

        if ($object->hasMethod('getShortcodePlaceHolder')) {
            $attributes = null;

            if ($shortcode = $request->requestVar('sc')) {
                $shortcode = str_replace("\xEF\xBB\xBF", '', $shortcode); //remove BOM inside string on cursor position...
                $shortcodeData = ShortcodeParser::get_active()->extractTags($shortcode);
                if (isset($shortcodeData[0])) {
                    $attributes = $shortcodeData[0]['attrs'];
                }
            }

            return $object->getShortcodePlaceholder($attributes);
        }

        // default: return an URL to a SVG shortcode placeholder image
        $defaults = self::config()->get('default_placeholder');
        $width = Config::inst()->get($sc_class, 'shortcode_close_parent') ? $defaults['full_width'] : 60 + floor( ((int) $defaults['fontsize']) / 1.8 * strlen($request->requestVar('sc')) );
        $height = Config::inst()->get($sc_class, 'shortcode_close_parent') ? $defaults['full_height'] : $defaults['height'];
        // allow overriding by a config on the SC object class
        $sc_defaults = Config::inst()->get($sc_class, 'placeholder_settings');
        if(isset($sc_defaults['width'])) $width = $sc_defaults['width'];
        if(isset($sc_defaults['height'])) $height = $sc_defaults['height'];

        return $this->redirect($this->Link("placehold.img") . '?' . http_build_query([ 'w' => $width, 'h' => $height, 'txt' => $request->requestVar('sc') ]));
    }

}

