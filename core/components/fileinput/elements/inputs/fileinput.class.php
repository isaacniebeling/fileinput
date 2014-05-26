<?php
/**
 * Class fileInput
 * 
 * sweet! now you can upload files!.
 */
class fileInput extends cbBaseInput {
    public $defaultIcon = 'attachment';
    public $defaultTpl = '<li><a href="[[+url]]" title="[[+title]]">[[+title]] ([[+size:fileinput.formatsize]])</a></li>';
    public $defaultWrapperTpl = '<ul class="files">[[+files]]</ul>';

    /**
     * Make sure the fileinput lexicon is loaded
     *
     * @param ContentBlocks $contentBlocks
     * @param array $options
     */
    public function __construct(ContentBlocks $contentBlocks, array $options = array())
    {
        parent::__construct($contentBlocks, $options);
        $this->modx->lexicon->load('fileinput:default');
    }

    /**
     * Load the main input javascript.
     *
     * @return array
     */
    public function getJavaScripts()
    {
        $assetsUrl = $this->modx->getOption('fileinput.assets_url', null, MODX_ASSETS_URL . 'components/fileinput/');
        return array(
            $assetsUrl . 'js/file.input.js',
        );
    }

    /**
     * Load the template for the input, and also set a JS variable so the JS can find the connector.
     *
     * @return array
     */
    public function getTemplates()
    {    
        $tpls = array();

        // Grab the template
        $corePath = $this->modx->getOption('fileinput.core_path', null, MODX_CORE_PATH . 'components/fileinput/');
        $innerTemplate = file_get_contents($corePath . 'templates/partials/fileinput_item.tpl'); //wrapTpl to this
        $wrapperTemplate = file_get_contents($corePath . 'templates/fileinput.tpl'); //wrapInputTpl to this

        // Add the connector url to the manager page
        $url = $this->modx->getOption('fileinput.assets_url', null, MODX_ASSETS_URL . 'components/fileinput/');
        $url .= 'connector.php';

        if ($this->modx->controller) {
            $this->modx->controller->addHtml('<script type="text/javascript">
                var fileInputConnectorUrl = "'.$url.'";
            </script>');
            $this->modx->controller->addLexiconTopic('fileinput:default');
        }
        
//        $innerTemplate = str_replace('[[+url]]', $url, $innerTemplate);
        $tpls[] = $this->contentBlocks->wrapInputTpl('fileinput', $wrapperTemplate);
        $tpls[] = $this->contentBlocks->wrapTpl('contentblocks-field-fileinput_file', $innerTemplate);
        return $tpls;
    }

    /**
     * Return the name for the input from the lexicon.
     *
     * @return string
     */
    public function getName()
    {
        return $this->modx->lexicon('fileinput');
    }

    /**
     * Return the description for the input from the lexicon.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->modx->lexicon('fileinput.description');
    }
    
    /**
     * @return array
     */
    public function getFieldProperties()
    {
        return array(
            array(
                'key' => 'wrapper_template',
                'fieldLabel' => $this->modx->lexicon('contentblocks.wrapper_template'),
                'xtype' => 'code',
                'default' => $this->defaultWrapperTpl,
                'description' => $this->modx->lexicon('contentblocks.wrapper_template.description')
            ),
            array(
                'key' => 'max_files',
                'fieldLabel' => $this->modx->lexicon('fileinput.max_files'),
                'xtype' => 'numberfield',
                'default' => 12,
                'description' => $this->modx->lexicon('fileinput.max_files.description')
            ),
            array(
                'key' => 'source',
                'fieldLabel' => $this->modx->lexicon('contentblocks.image.source'),
                'xtype' => 'contentblocks-combo-mediasource',
                'default' => 0,
                'description' => $this->modx->lexicon('contentblocks.image.source.description')
            ),
            array(
                'key' => 'directory',
                'fieldLabel' => $this->modx->lexicon('fileinput.directory'),
                'xtype' => 'textfield',
                'default' => 'assets/uploads/files',
                'description' => $this->modx->lexicon('fileinput.directory.description')
            ),
            array(
                'key' => 'file_types',
                'fieldLabel' => $this->modx->lexicon('fileinput.file_types'),
                'xtype' => 'textfield',
                'default' => 'pdf,doc,docx,xls,xlsx,txt,ppt,pptx',
                'description' => $this->modx->lexicon('fileinput.file_types.description')
            ),
        );
    }

    /**
     * Process this field based on a row and a wrapper tpl
     *
     * @param cbField $field
     * @param array $data
     * @return mixed
     */
    public function process(cbField $field, array $data = array())
    {  
        $settings = $data;
        unset($settings['files']);

        $rowTpl = $field->get('template');
        $wrapperTpl = $field->get('wrapper_template');

        $output = array();
        $idx = 1;
        foreach ($data['files'] as $file) {
            $file = array_merge($settings, $file);
            $file['idx'] = $idx;
            $output[] = $this->contentBlocks->parse($rowTpl, $file);
            $idx++;
        }
        $output = implode('', $output);
        $settings['total'] = count($data['files']);
        $settings['files'] = $output;
        return $this->contentBlocks->parse($wrapperTpl, $settings);
    }
}
