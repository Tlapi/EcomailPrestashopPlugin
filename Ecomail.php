<?php

    if( !defined( '_PS_VERSION_' ) ) {
        exit;
    }

    if( !class_exists( 'ModuleBase' ) ) {
        require_once __DIR__ . '/lib/moduleBase.php';
    }

    class Ecomail extends ModuleBase {

        public function __construct() {
            $this->name    = 'Ecomail';
            $this->version = '1.1.0';
            $this->author  = 'Ecomail.cz s.r.o.';
            if( version_compare(
                    _PS_VERSION_,
                    '1.5',
                    '>'
            )
            ) {
                $this->tab = 'emailing';
            }
            else {
                $this->tab = 'advertising_marketing';
            }
            $this->need_instance = 0;

            /**
             * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
             */
            $this->bootstrap = true;

            parent::__construct();

            $this->displayName = $this->l( 'Ecomail.cz' );
            $this->description = $this->l( 'Tento modul prováže Váš Prestashop s Vaším účtem u ecomail.cz' );

            $this->confirmUninstall = $this->l( 'Opravdu chcete modul odinstalovat?' );

        }

        public function hookActionCustomerAccountAdd( $params ) {

            if( $params['_POST']['newsletter'] ) {

                if( $this->getConfigurationValue( 'api_key' ) ) {

                    $this->getAPI()
                         ->subscribeToList(
                         $this->getConfigurationValue( 'list_id' ),
                         array(
                                 'email' => $params['_POST']['email'],
                                 'name'  => $params['_POST']['customer_firstname'] . ' ' . $params['_POST']['customer_lastname']
                         )
                            );

                }
            }

        }

        public function hookActionValidateOrder( $params ) {

            $r = $this->getAPI()
                      ->createTransaction( $params['order'] );

        }

        public function hookDisplayFooter() {

            $output = '';

            $appId = $this->getConfigurationValue( 'app_id' );

            if( $appId ) {

                $this->context->controller->addJS( $this->_path . 'views/js/front.js' );

                $html = <<<HTML
                
<!-- Ecomail starts -->
<script type="text/javascript">
;(function(p,l,o,w,i,n,g){if(!p[i]){p.GlobalSnowplowNamespace=p.GlobalSnowplowNamespace||[];
p.GlobalSnowplowNamespace.push(i);p[i]=function(){(p[i].q=p[i].q||[]).push(arguments)
};p[i].q=p[i].q||[];n=l.createElement(o);g=l.getElementsByTagName(o)[0];n.async=1;
n.src=w;g.parentNode.insertBefore(n,g)}}(window,document,"script","//d1fc8wv8zag5ca.cloudfront.net/2.4.2/sp.js","ecotrack"));
window.ecotrack('newTracker', 'cf', 'd2dpiwfhf3tz0r.cloudfront.net', {1});
window.ecotrack('setUserIdFromLocation', 'ecmid');
window.ecotrack('trackPageView');
</script>
<!-- Ecomail stops -->
HTML;

                $html = strtr(
                        $html,
                        array(
                                '{1}' => Tools::jsonEncode(
                                              array(
                                                      'appId' => $appId
                                              )
                                        )
                        )
                );

                $output .= $html;

                $html = <<<HTML
<script type="text/javascript">            
    EcomailFront.init({1});
</script>
HTML;

                $html = strtr(
                        $html,
                        array(
                                '{1}' => Tools::jsonEncode(
                                              array(
                                                      'cookieNameTrackStructEvent' => $this->getCookieNameTrackStructEvent(
                                                              )
                                              )
                                        )
                        )
                );

                $output .= $html;
            }

            return $output;

        }

        public function trackAddToCart( $id_product, $id_product_attribute, $quantity ) {

            setcookie(
                    $this->getCookieNameTrackStructEvent(),
                    Tools::jsonEncode(
                         array(
                                 'category' => 'Product',
                                 'action'   => 'AddToCart',
                                 'tag'      => implode(
                                         '|',
                                         array(
                                                 $id_product,
                                                 $id_product_attribute
                                         )
                                 ),
                                 'property' => 'quantity',
                                 'value'    => $quantity
                         )
                    )
            );

        }

        /**
         * Don't forget to create update methods if needed:
         * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
         */
        public function install() {

            return parent::install()
                   && $this->registerHook( 'actionCustomerAccountAdd' )
                   && $this->registerHook( 'actionValidateOrder' )
                   && $this->registerHook( 'displayFooter' );
        }

        public function uninstall() {

            return parent::uninstall();
        }

        /**
         * Load the configuration form
         */
        public function getContent() {

            if( Tools::getValue( 'ajax' ) ) {

                $result = array();

                $cmd = Tools::getValue( 'cmd' );
                if( $cmd == 'getLists' ) {

                    $APIKey = Tools::getValue( 'APIKey' );
                    if( $APIKey ) {
                        $listsCollection = $this->getAPI()
                                                ->setAPIKey( $APIKey )
                                                ->getListsCollection();
                        foreach( $listsCollection as $list ) {
                            $result[] = array(
                                    'id'   => $list->id,
                                    'name' => $list->name
                            );
                        }
                    }

                }

                die( Tools::jsonEncode( $result ) );
            }

            $output = '';
            if( !extension_loaded( 'curl' ) ) {
                $output .= $this->displayError(
                                $this->l( 'Musíte mít povolenu cURL extension abyste mohli používat tento modul.' )
                );
            }
            else {
                /**
                 * If values have been submitted in the form, process.
                 */
                if( ( (bool)Tools::isSubmit(
                                 $this->getSubmitActionName()
                        ) ) == true
                ) {
                    $ecomail_list_id = Tools::getValue( $this->getConfigurationName( 'list_id' ) );
                    if( !$ecomail_list_id
                        || empty( $ecomail_list_id )
                    ) {
                        $output .= $this->displayError( $this->l( 'Zadaná data nejsou správná.' ) );
                    }
                    $this->postProcess();
                }
            }

            $this->context->smarty->assign(
                                  'module_dir',
                                  $this->_path
            );

            $output .= $this->context->smarty->fetch( $this->local_path . 'views/templates/admin/configure.tpl' );

            $output .= $this->renderForm();

            $html = <<<HTML
<script type="text/javascript">            
    EcomailBackOffice.init({1});
</script>
HTML;

            $html = strtr(
                    $html,
                    array(
                            '{1}' => Tools::jsonEncode(
                                          array(
                                                  'formFieldAPIKey' => $this->getConfigurationName( 'api_key' ),
                                                  'formFieldList'   => $this->getConfigurationName( 'list_id' ),
                                                  'ajaxUrl'         => $this->context->link->getAdminLink(
                                                                                           'AdminModules',
                                                                                           false
                                                                       ) . '&configure=' . $this->name . '&ajax=1&token=' . Tools::getAdminTokenLite(
                                                                                                                                 'AdminModules'
                                                                       )
                                          )
                                    )
                    )
            );

            $output .= $html;

            return $output;
        }

        /**
         * Create the structure of your form.
         */
        protected function getConfigForm() {

            $options = array();

            if( $this->getConfigurationValue( 'api_key' ) ) {
                $listsCollection = $this->getAPI()
                                        ->getListsCollection();
                foreach( $listsCollection as $list ) {
                    $options[] = array(
                            'id_option' => $list->id,
                            'name'      => $list->name
                    );
                }
            }

            $form                  = parent::getConfigForm();
            $form['form']['input'] = array_merge(
                    $form['form']['input'],
                    array(
                            array(
                                    'type'     => 'text',
                                    'label'    => $this->l( 'Vložte Váš API klíč' ),
                                    'name'     => $this->getConfigurationName( 'api_key' ),
                                    'rows'     => 20,
                                    'required' => true
                            ),
                            array(
                                    'type'     => 'select',
                                    'label'    => $this->l( 'Vyberte list:' ),
                                    'desc'     => $this->l(
                                                       'Vyberte list do kterého budou zapsáni noví zákazníci'
                                            ),
                                    'name'     => $this->getConfigurationName( 'list_id' ),
                                    'required' => true,
                                    'options'  => array(
                                            'query' => $options,
                                            'id'    => 'id_option',
                                            'name'  => 'name'
                                    )
                            ),
                            array(
                                    'type'  => 'text',
                                    'label' => $this->l( 'Vložte Vaše appId' ),
                                    'desc'  => $this->l(
                                                    'Tento údaj slouží pro aktivaci funkce Trackovací kód'
                                            ),
                                    'name'  => $this->getConfigurationName( 'app_id' ),
                                    'rows'  => 20
                            )
                    )
            );

            return $form;
        }

        protected function getConfigurationNames() {
            return array_merge(
                    parent::getConfigurationNames(),
                    array(
                            'api_key',
                            'app_id',
                            'list_id'
                    )
            );
        }

        protected function getAPI() {

            require_once __DIR__ . '/lib/api.php';

            $obj = new EcomailAPI();
            $obj->setAPIKey( $this->getConfigurationValue( 'api_key' ) );

            return $obj;
        }

        protected function getCookieNameTrackStructEvent() {
            return $this->name;
        }

    }
