<?php
/**
 * uploading class for ContentBlocks fileinput field
 */

global $modx;
$cbPath = $modx->getOption('contentblocks.core_path',null,$modx->getOption('core_path').'components/contentblocks/');
require_once $cbPath.'processors/content/image/upload.class.php';

class ContentBlocksFileUploadProcessor extends ContentBlocksImageUploadProcessor
{
    /** @var null|modMediaSource $source */
    public $source = null;
    public $path = '';
    public $fileErrors = array();
    /** @var cbField $field */
    public $field;

    /**
     * @return bool|string
     */
    public function initialize()
    {
        if (!$this->_getSource()) {
            return $this->modx->lexicon('contentblocks.error_loading_source');
        }
        $fieldPath = false;
        $fieldId = (int)$this->getProperty('field');
        if ($fieldId > 0 && $field = $this->modx->getObject('cbField', $fieldId)) {
            $fieldPath = $field->get('directory');
            $this->field = $field;
        }

        // make sure it ends in a /. If it's false, keep it that way.
        $fieldPath = $fieldPath ? rtrim($fieldPath, '/') . '/' : $fieldPath;

        $path = $fieldPath ? $fieldPath : $this->modx->getOption('contentblocks.file.upload_path', null, 'assets/uploads/');
        $path = str_replace(array(
            '[[+year]]',
            '[[+month]]',
            '[[+day]]',
            '[[+user]]',
            '[[+username]]',
            '[[+resource]]',
        ), array(
            date('Y'),
            date('m'),
            date('d'),
            $this->modx->user->get('id'),
            $this->modx->user->get('username'),
            (int)$this->getProperty('resource', 0),
        ), $path);
        $this->path = $path;

        /**
         * Make sure the upload path exists. We unset errors to prevent issues if it already exists.
         */
        $this->source->createContainer($this->path, '/');
        $this->source->errors = array();
        return true;
    }

    /**
     * @return bool
     */
    public function process()
    {
        if (!$this->source->checkPolicy('create')) {
            return $this->failure($this->modx->lexicon('permission_denied'));
        }

        $file = $_FILES['file'];
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileTypes = $this->field->get('file_types');
        if ($fileTypes) {
            $fileTypes = explode(',', $fileTypes);
            if (!in_array($fileExtension, $fileTypes)) {
                $errors = $this->modx->lexicon('fileinput.file_types.disallowed');
                return $this->failure($errors);
            }
        }

        if ($this->modx->getOption('contentblocks.file.sanitize', null, true)) {
            $fileName = $this->modx->contentblocks->sanitize($fileName);
        }
        if ($this->modx->getOption('contentblocks.file.hash_name', null, false)) {
            $fileName = md5($fileName);
        }
        if ($this->modx->getOption('contentblocks.file.prefix_time', null, false)) {
            $fileName = time() . '_' . $fileName;
        }
        $bases = $this->source->getBases($this->path);
        /// don't overwrite previous files that were uploaded
        $i = 0;
        $tpFileName = $fileName;
        while (file_exists($bases['pathAbsoluteWithPath'] . $tpFileName . '.' . $fileExtension)) {
            $i++;
            $tpFileName = $fileName . '_' . $i;
        }

        $_FILES['file']['name'] = $tpFileName . '.' . $fileExtension;

        /**
         * Do the upload
         */

        $uploaded = $this->source->uploadObjectsToContainer($this->path, $_FILES);

        if (!$uploaded) {
            $errors = $this->source->getErrors();
            $errors = implode('<br />', $errors);
            return $this->failure($errors);
        }

        return $this->success('', array(
            'url' => $this->source->getObjectUrl($this->path . $_FILES['file']['name']),
            'size' => $_FILES['file']['size'],
            'extension' => $fileExtension,
        ));
    }
}

return 'ContentBlocksFileUploadProcessor';
