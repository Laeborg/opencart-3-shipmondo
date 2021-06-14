<?php
class ControllerExtensionShippingShipmondo extends Controller
{
	public function getPickupPoint()
    {
        $this->session->data['pickup_point'] = $this->request->get['pup_id'];
        
        if(!empty($this->request->get['carrier']) && isset($this->request->get['pup_id']))
        {
            $pickup_point = $this->cache->get(strtolower('shipmondo.pup.' . $this->request->get['carrier'] . '.' . $this->request->get['pup_id']));
            
            if(empty($pickup_point))
            {
                $shipmondo = new Shipmondo($this->config->get('shipping_shipmondo_api_user'), $this->config->get('shipping_shipmondo_api_key'));
                
                $pups = $shipmondo->getPickupPoints([
                    'carrier_code' => $this->request->get['carrier'],
                    'country_code' => isset($this->request->get['country_code']) ? $this->request->get['country_code'] : '',
                    'id' => $this->request->get['pup_id'],
                ]);
                
                $pickup_point = [];
                
                if(!empty($pups['output']))
                {
                    foreach($pups['output'] as $pup)
                    {
                        $pickup_point = $pup;
                    }
                }
                
                $this->cache->set(strtolower('shipmondo.pup.' . $this->request->get['carrier'] . '.' . $this->request->get['pup_id']), $pickup_point);
            }
            
            $this->response->addHeader('Content-Type: application/json');
    		$this->response->setOutput(json_encode($pickup_point));
        }
    }
}