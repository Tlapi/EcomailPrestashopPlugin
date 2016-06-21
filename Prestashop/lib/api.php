<?php

    class EcomailAPI {

        protected $APIKey;

        public function setAPIKey( $arg ) {
            $this->APIKey = $arg;

            return $this;
        }

        public function getListsCollection() {

            return $this->call( 'lists' );

        }

        public function subscribeToList( $listId, $customerData ) {

            return $this->call(
                        sprintf(
                                'lists/%d/subscribe',
                                $listId
                        ),
                        'POST',
                        array(
                                'subscriber_data' => $customerData
                        )
            );

        }

        public function createTransaction( Order $order ) {

            $shopUrl         = new ShopUrl( $order->id_shop );
            $addressDelivery = new Address( $order->id_address_delivery );

            $arr = array();
            foreach( $order->getProducts() as $orderProduct ) {
                $product  = new Product( $orderProduct['product_id'] );
                $category = new Category( $product->getDefaultCategory() );
                $arr[]    = array(
                        'code'      => $orderProduct['product_reference'],
                        'title'     => $orderProduct['product_name'],
                        'category'  => $category->getName(),
                        'price'     => $orderProduct['unit_price_tax_incl'],
                        'amount'    => $orderProduct['product_quantity'],
                        'timestamp' => strtotime( $order->date_add )
                );
            }

            return $this->call(
                        'tracker/transaction',
                        'POST',
                        array(
                                'transaction'       => array(
                                        'order_id'  => $order->id,
                                        'email'     => $order->getCustomer()->email,
                                        'shop'      => $shopUrl->getURL(),
                                        'amount'    => $order->total_paid_tax_incl,
                                        'tax'       => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                                        'shipping'  => $order->total_shipping,
                                        'city'      => $addressDelivery->city,
                                        'county'    => '',
                                        'country'   => $addressDelivery->country,
                                        'timestamp' => strtotime( $order->date_add )
                                ),
                                'transaction_items' => $arr
                        )
            );

        }

        protected function call( $url, $method = 'GET', $data = null ) {
            $ch = curl_init();

            curl_setopt(
                    $ch,
                    CURLOPT_URL,
                    "http://api2.ecomailapp.cz/" . $url
            );
            curl_setopt(
                    $ch,
                    CURLOPT_RETURNTRANSFER,
                    TRUE
            );
            curl_setopt(
                    $ch,
                    CURLOPT_HEADER,
                    FALSE
            );
            curl_setopt(
                    $ch,
                    CURLOPT_HTTPHEADER,
                    array(
                        "Content-Type: application/json",
                            'Key: ' . $this->APIKey
                    )
            );

            if( in_array(
                    $method,
                    array(
                            'POST',
                            'PUT'
                    )
            )
            ) {

                curl_setopt(
                        $ch,
                        CURLOPT_CUSTOMREQUEST,
                        $method
                );

                curl_setopt(
                        $ch,
                        CURLOPT_POSTFIELDS,
                        json_encode( $data )
                );
                
            }

            $response = curl_exec( $ch );
            curl_close( $ch );

            return json_decode( $response );
        }

    }