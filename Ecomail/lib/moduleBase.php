<?php

    if( !defined( '_PS_VERSION_' ) ) {
        exit;
    }

    abstract class ModuleBase extends Module {

        protected $config_form = false;

        public function hookDisplayBackOfficeHeader() {

            if( Tools::getValue( 'module_name' ) == $this->name ) {
                $this->context->controller->addJquery();
                $this->context->controller->addJS( $this->_path . 'views/js/back.js' );
                $this->context->controller->addCSS( $this->_path . 'views/css/back.css' );
            }

        }

        /**
         * Don't forget to create update methods if needed:
         * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
         */
        public function install() {

            return parent::install()
                   && $this->registerHook( 'displayBackOfficeHeader' )
                   && $this->maybeUpdateDatabase();
        }

        public function uninstall() {

            return parent::uninstall();
        }

        protected function maybeUpdateDatabase() {

            return true;
        }

        /**
         * Create the form that will be displayed in the configuration of your module.
         */
        protected function renderForm() {
            $helper = new HelperForm();

            $helper->show_toolbar             = false;
            $helper->table                    = $this->table;
            $helper->module                   = $this;
            $helper->default_form_language    = $this->context->language->id;
            $helper->allow_employee_form_lang = Configuration::get(
                                                             'PS_BO_ALLOW_EMPLOYEE_FORM_LANG',
                                                             0
            );

            $helper->identifier    = $this->identifier;
            $helper->submit_action = $this->getSubmitActionName();
            $helper->currentIndex  = $this->context->link->getAdminLink(
                                                         'AdminModules',
                                                         false
                                     ) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
            $helper->token         = Tools::getAdminTokenLite( 'AdminModules' );

            $helper->tpl_vars = array(
                    'fields_value' => $this->getConfigFormValues(),
                    /* Add values for your inputs */
                    'languages'    => $this->context->controller->getLanguages(),
                    'id_language'  => $this->context->language->id,
            );

            return $helper->generateForm( array( $this->getConfigForm() ) );
        }

        /**
         * Create the structure of your form.
         */
        protected function getConfigForm() {

            return array(
                    'form' => array(
                            'legend' => array(
                                    'title' => $this->l( 'Nastavení' ),
                                    'icon'  => 'icon-cogs',
                            ),
                            'input'  => array(
                            ),
                            'submit' => array(
                                    'title' => $this->l( 'Uložit' ),
                            ),
                    ),
            );
        }

        /**
         * Set values for the inputs.
         */
        protected function getConfigFormValues() {

            $return = array();
            foreach( $this->getConfigurationNames() as $name ) {
                $return[$this->getConfigurationName( $name )] = $this->getConfigurationValue( $name );
            }

            return $return;
        }

        /**
         * Save form data.
         */
        protected function postProcess() {
            $form_values = $this->getConfigFormValues();

            foreach( array_keys( $form_values ) as $key ) {
                Configuration::updateValue(
                             $key,
                             trim( Tools::getValue( $key ) )
                );
            }

        }

        protected function getSubmitActionName() {
            return sprintf(
                    'submit%sModule',
                    $this->name
            );
        }

        protected function getConfigurationName( $key ) {
            return sprintf(
                    '%s_%s',
                    $this->name,
                    $key
            );
        }

        protected function getConfigurationValue( $key ) {
            return Configuration::get(
                                $this->getConfigurationName( $key )
            );
        }

        protected function getConfigurationNames() {
            return array(
            );
        }

    }
