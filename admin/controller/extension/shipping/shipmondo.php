<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

class ControllerExtensionShippingShipmondo extends Controller
{
    private $error = [];
    
	public function index()
    {
        $this->load->language('extension/shipping/shipmondo');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
        
        $this->load->model('localisation/geo_zone');
        
        $this->load->model('localisation/country');
        
        $this->load->model('localisation/tax_class');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_shipmondo', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}
        
        if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
        
        $data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/shipmondo', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/shipping/shipmondo', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
        
        // API settings
        if (isset($this->request->post['shipping_shipmondo_api_user'])) {
			$data['shipping_shipmondo_api_user'] = $this->request->post['shipping_shipmondo_api_user'];
		} elseif ($this->config->has('shipping_shipmondo_api_user')) {
			$data['shipping_shipmondo_api_user'] = $this->config->get('shipping_shipmondo_api_user');
		} else {
			$data['shipping_shipmondo_api_user'] = '';
		}
        
        if (isset($this->request->post['shipping_shipmondo_api_key'])) {
			$data['shipping_shipmondo_api_key'] = $this->request->post['shipping_shipmondo_api_key'];
		} elseif ($this->config->has('shipping_shipmondo_api_key')) {
			$data['shipping_shipmondo_api_key'] = $this->config->get('shipping_shipmondo_api_key');
		} else {
			$data['shipping_shipmondo_api_key'] = '';
		}
        
        // Shipmondo settings
        if (isset($this->request->post['shipping_shipmondo_status'])) {
			$data['shipping_shipmondo_status'] = $this->request->post['shipping_shipmondo_status'];
		} elseif ($this->config->has('shipping_shipmondo_status')) {
			$data['shipping_shipmondo_status'] = $this->config->get('shipping_shipmondo_status');
		} else {
			$data['shipping_shipmondo_status'] = false;
		}
        
        // Shipmondo settings
        if (isset($this->request->post['shipping_shipmondo_sender_country_code'])) {
			$data['shipping_shipmondo_sender_country_code'] = $this->request->post['shipping_shipmondo_sender_country_code'];
		} elseif ($this->config->has('shipping_shipmondo_sender_country_code')) {
			$data['shipping_shipmondo_sender_country_code'] = $this->config->get('shipping_shipmondo_sender_country_code');
		} else {
			$data['shipping_shipmondo_sender_country_code'] = false;
		}
        
        if (isset($this->request->post['shipping_shipmondo_label_type'])) {
			$data['shipping_shipmondo_label_type'] = $this->request->post['shipping_shipmondo_label_type'];
		} elseif ($this->config->has('shipping_shipmondo_label_type')) {
			$data['shipping_shipmondo_label_type'] = $this->config->get('shipping_shipmondo_label_type');
		} else {
			$data['shipping_shipmondo_label_type'] = 'draft';
		}

        if (isset($this->request->post['shipping_shipmondo_own_agreement'])) {
			$data['shipping_shipmondo_own_agreement'] = $this->request->post['shipping_shipmondo_own_agreement'];
		} elseif ($this->config->has('shipping_shipmondo_own_agreement')) {
			$data['shipping_shipmondo_own_agreement'] = $this->config->get('shipping_shipmondo_own_agreement');
		} else {
			$data['shipping_shipmondo_own_agreement'] = false;
		}

        if (isset($this->request->post['shipping_shipmondo_auto_print'])) {
			$data['shipping_shipmondo_auto_print'] = $this->request->post['shipping_shipmondo_auto_print'];
		} elseif ($this->config->has('shipping_shipmondo_auto_print')) {
			$data['shipping_shipmondo_auto_print'] = $this->config->get('shipping_shipmondo_auto_print');
		} else {
			$data['shipping_shipmondo_auto_print'] = 0;
		}
        
        if (isset($this->request->post['shipping_shipmondo_pickup_points'])) {
			$data['shipping_shipmondo_pickup_points'] = $this->request->post['shipping_shipmondo_pickup_points'];
		} elseif ($this->config->has('shipping_shipmondo_pickup_points')) {
			$data['shipping_shipmondo_pickup_points'] = $this->config->get('shipping_shipmondo_pickup_points');
		} else {
			$data['shipping_shipmondo_pickup_points'] = 'dropdown';
		}
        
        if (isset($this->request->post['shipping_shipmondo_google_api_key'])) {
			$data['shipping_shipmondo_google_api_key'] = $this->request->post['shipping_shipmondo_google_api_key'];
		} elseif ($this->config->has('shipping_shipmondo_google_api_key')) {
			$data['shipping_shipmondo_google_api_key'] = $this->config->get('shipping_shipmondo_google_api_key');
		} else {
			$data['shipping_shipmondo_google_api_key'] = '';
		}
        
        // Shipping Methods
        if (isset($this->request->post['shipping_shipmondo_methods'])) {
			$data['shipping_shipmondo_methods'] = $this->request->post['shipping_shipmondo_methods'];
		} elseif ($this->config->has('shipping_shipmondo_methods')) {
			$data['shipping_shipmondo_methods'] = $this->config->get('shipping_shipmondo_methods');
		} else {
			$data['shipping_shipmondo_methods'] = [];
		}
        
        foreach ($data['shipping_shipmondo_methods'] as $method_id => $method) {
            $data['shipping_shipmondo_methods'][$method_id]['carriers'] = [];
            $data['shipping_shipmondo_methods'][$method_id]['products'] = [];
            
            if ($method['geo_zone_id']) {
                $country = false;
                $zones = $this->model_localisation_geo_zone->getZoneToGeoZones($method['geo_zone_id']);

                if ($zones) {
                    foreach ($zones as $zone) {
                        $country = $this->model_localisation_country->getCountry($zone['country_id']);
                        break;
                    }
                }
                
                if ($country) {
                    $data['shipping_shipmondo_methods'][$method_id]['carriers'] = $this->getCarriers($country['iso_code_2']);
                    
                    if ($method['carrier_id']) {
                        foreach ($data['shipping_shipmondo_methods'][$method_id]['carriers'] as $carrier) {
                            if ($method['carrier_id'] == $carrier['id']) {
                                $data['shipping_shipmondo_methods'][$method_id]['products'] = $this->getProducts($carrier['code'], $country['iso_code_2']);
                            }
                        }
                    }
                }
            }
        }
        
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
        
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
        
        $data['user_token'] = $this->session->data['user_token'];

		$this->response->setOutput($this->load->view('extension/shipping/shipmondo', $data));
    }

	protected function validate()
    {
		if(!$this->user->hasPermission('modify', 'extension/shipping/shipmondo'))
        {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
    
    protected function getCarriers($country_code = '')
    {
        $carrier_data = $this->cache->get(strtolower('shipmondo.carriers.' . $country_code));
        
        if(empty($carrier_data))
        {
            if(!empty($country_code))
            {
                $shipmondo = new Shipmondo($this->config->get('shipping_shipmondo_api_user'), $this->config->get('shipping_shipmondo_api_key'));
                
                $carriers = $shipmondo->getCarriers(['country_code' => $country_code]);
                
                if(!empty($carriers['output']))
                {
                    foreach($carriers['output'] as $carrier)
                    {
                        $carrier_data[] = $carrier;
                    }
                    
                    $this->cache->set(strtolower('shipmondo.carriers.' . $country_code), $carrier_data);
                }
            }
        }
        
        return $carrier_data;
    }
    
    protected function getProducts($carrier_code = '', $receiver_country_code = '')
    {
        $product_data = $this->cache->get(strtolower('shipmondo.products.' . $carrier_code . '-' . $receiver_country_code));
        
        if(empty($product_data))
        {
            if(!empty($carrier_code) && !empty($receiver_country_code))
            {
                $shipmondo = new Shipmondo($this->config->get('shipping_shipmondo_api_user'), $this->config->get('shipping_shipmondo_api_key'));
                
                $products = $shipmondo->getProducts(['carrier_code' => $carrier_code, 'sender_country_code' => $this->config->get('shipping_shipmondo_sender_country_code'), 'receiver_country_code' => $receiver_country_code]);
                
                if(!empty($products['output']))
                {
                    foreach($products['output'] as $product)
                    {
                        $product_data[] = $product;
                    }
                    
                    $this->cache->set(strtolower('shipmondo.products.' . $carrier_code . '-' . $receiver_country_code), $product_data);
                }
            }
        }
        
        return $product_data;
    }
    
    public function ajax()
    {
        $this->load->model('localisation/geo_zone');
        
        $this->load->model('localisation/country');
        
        $json = [
            'carriers' => [],
            'products' => []
        ];
        
        if(!empty($this->request->get['geo_zone_id']))
        {
            $country = false;
            $zones = $this->model_localisation_geo_zone->getZoneToGeoZones($this->request->get['geo_zone_id']);

            if($zones)
            {
                foreach($zones as $zone)
                {
                    $country = $this->model_localisation_country->getCountry($zone['country_id']);
                    break;
                }
            }
            
            if($country)
            {
                $carriers = $this->getCarriers($country['iso_code_2']);
                
                if($carriers)
                {
                    $json['carriers'] = $carriers;
                }
                
                if(!empty($this->request->get['carrier_id']))
                {
                    foreach($json['carriers'] as $carrier)
                    {
                        if($carrier['id'] == $this->request->get['carrier_id'])
                        {
                            $products = $this->getProducts($carrier['code'], $country['iso_code_2']);
                            
                            if($products)
                            {
                                $json['products'] = $products;
                            }
                        }
                    }
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }
}