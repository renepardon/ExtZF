<?php
/**
 * @SuppressWarnings("PMD")
 */
/**
 *
 * Plugin for modular language files. Loads a global langauge file,
 * and one for the current plugin we're in.
 *
 */
class Extzf_Controller_Plugin_I18n extends Zend_Controller_Plugin_Abstract
{

    protected $_modules = array();
    protected $_translateDir = null;
    protected $_locale = null;
    protected $_adapter = null;


    /**
     * Initializes the i18n plugin
     * @return void
     */
    public function _init()
    {
        $config = Zend_Registry::get('config');
        $this->_locale = Zend_Registry::get('Locale');
        $this->_translateDir = $config->translate->dirname;
        $this->_adapter = strtolower($config->translate->adapter);
    }


    /**
     * Loads translation files module based
     *
     * @access public
     * @param Zend_Controller_Request_Abstract $_request
     * @return void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->_init();
        $this->_registerTranslation();
    }


    /**
     * Loads translation files module based
     *
     * @access public
     * @param Zend_Controller_Request_Abstract $_request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_addTranslationModule($request->getModuleName());
    }


    /**
    * Returns a path to a translation file, based on the
    * input parameters
    *
    * @access protected
    * @param string $dir the file directory
    * @param string $locale the locale of the translation file (e.g. 'en')
    * @param string $adapter the format of the translation file (e.g. 'csv�)
    * @return string The full path to the requested file
    */
    protected function _getFilePath($dir, $locale, $adapter)
    {
        switch ($adapter) {
            case 'csv':
                $file = sprintf(
                    '%s/%s.csv',
                    $dir,
                    $locale
                );
                break;
            default:
                return;
                break;
        }
        return $file;
    }


    /**
     * Registers the default translation file and adds it to the registry,
     * so it is available to other modules.
     * @return void
     */
    protected function _registerTranslation()
    {
        $dir = APPLICATION_PATH . '/' . $this->_translateDir;
        $file = $this->_getFilePath($dir, $this->_locale, $this->_adapter);

        // Build instance of Translation class
        try {
            $translation = new Zend_Translate($this->_adapter, realpath($file), $this->_locale);
            Zend_Registry::set('Zend_Translate', $translation);
            Zend_Registry::set('Translate', new Extzf_Translate());
        }
        catch (Exception $ex) {
            Zend_Registry::get('Logger')->log($ex->getMessage());
        }
    }


    /**
     * Adds a module language file to the current translation
     * adapter.
     * @param string $module The modules name
     * @return void
     */
    protected function _addTranslationModule ($module)
    {
        $dir = APPLICATION_PATH . '/modules/' . $module . '/';
        $dir.= $this->_translateDir;

        if (in_array($module, $this->_modules)) {
            return;
        }

        // Is existing?
        if (is_readable($dir)) {

            $file = $this->_getFilePath($dir, $this->_locale, $this->_adapter);

            // Add translation module
            try {
                $translation = Zend_Registry::get('Zend_Translate');
                $translation->getAdapter()->addTranslation($file);
                $this->_modules[] = $module;
            } catch (Exception $ex) {
                Zend_Registry::get('Logger')->log($ex->getMessage());
            }
        }
    }


    /**
     * Initializes the Ext.Direct routing way
     * since Ext.Direct has no FrontController to do that,
     * there needs to be a method to call from outer space.
     * Or e.g. a CLI script that needs translation.
     *
     * @param string $moduleName Optional module name for a module language to load
     * @return void
     */
    public function externInitTranslation($moduleName = false)
    {
        $this->_init();
        $this->_registerTranslation();

        // Add a translation module based
        if (isset($moduleName) && $moduleName !== '') {
            $this->_addTranslationModule($moduleName);
        }
    }
}