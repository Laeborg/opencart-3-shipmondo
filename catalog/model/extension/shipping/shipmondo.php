<?php
class ModelExtensionShippingShipmondo extends Model
{
    public function getQuote($address)
    {
        $this->load->language('extension/shipping/shipmondo');
        
        $quote_data = [];
        
        if($this->config->get('shipping_shipmondo_status'))
        {
            $methods = $this->config->get('shipping_shipmondo_methods');
            
            if($methods)
            {
                $weight = $this->cart->getWeight();
                
                foreach($methods as $method_id => $method)
                {
                    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$method['geo_zone_id'] . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
                    
                    if($query->num_rows)
                    {
                        if(!empty($method['weight']))
                        {
                            $weights = explode('-', $method['weight']);
                            
                            if($weights[0] > $weight || $weights[1] < $weight)
                            {
                                continue;
                            }
                        }
                        
                        $method_info = $this->getMethod($address['iso_code_2'], $method['carrier_id'], $method['product_id']);
                        
                        if($method_info)
                        {
                            if(!empty($method['free_shipping']))
                            {
                                if($this->cart->getSubTotal() >= $method['free_shipping'])
                                {
                                    $method['price'] = 0;
                                }
                            }
                            
                            $additional_html = '';
                            
                            if($method_info['service_point_available'])
                            {
                                $pickup_points = $this->getPickupPoints($method_info['carrier']['code'], $address);
                                
                                if($pickup_points)
                                {
                                    if($this->config->get('shipping_shipmondo_pickup_points') == 'modal')
                                    {
                                        $additional_html = '
                                        <div id="shipmondo-' . strtolower($method_info['code']) . '" style="">
                                            <b>' . $this->language->get('text_pickup_point') . ':</b><br />
                                            <span class="selected_pup">' . $this->language->get('text_automatic') . '</span>
                                            <a href="#" class="btn btn-default button-pup" data-toggle="modal" data-target="#modal-' . strtolower($method_info['code']) . '"><i class="fa fa-map" aria-hidden="true"></i> ' . $this->language->get("text_select_pup") . '</a>
                                            
                                            <div id="modal-' . strtolower($method_info['code']) . '" class="modal fade" role="dialog">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                            <h2 class="modal-title">' . $this->language->get("text_select_pup") . '</h2>
                                                            <h4 class="modal-title">' . sprintf($this->language->get("text_results"), count($pickup_points)) . '</h4>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div id="map-' . strtolower($method_info['code']) . '"></div>
                                                            <div class="pickup_points">
                                                                <div class="row">
                                                                    ';
                                                                    foreach($pickup_points as $pickup_point)
                                                                    {
                                                                        if($this->session->data['pickup_point'] == $pickup_point["id"])
                                                                        {
                                                                            $additional_html .= '
                                                                                <div class="col-sm-6">
                                                                                    <input id="pup-' . $pickup_point["id"] . '" type="radio" name="shipmondo-pup" value="' . $pickup_point["id"] . '" checked="checked" />
                                                                                    <label for="pup-' . $pickup_point["id"] . '">
                                                                                        <b>' . $pickup_point["company_name"] . '</b><br />
                                                                                        ' . $pickup_point["address"] . '<br />
                                                                                        ' . ($pickup_point["address2"] ? $pickup_point["address2"] . "<br />" : "") . '
                                                                                        ' . $pickup_point["zipcode"] . ' ' . $pickup_point["city"] . '
                                                                                    </label>
                                                                                </div>
                                                                            ';
                                                                        }
                                                                        else
                                                                        {
                                                                            $additional_html .= '
                                                                                <div class="col-sm-6">
                                                                                    <input id="pup-' . $pickup_point["id"] . '" type="radio" name="shipmondo-pup" value="' . $pickup_point["id"] . '" />
                                                                                    <label for="pup-' . $pickup_point["id"] . '">
                                                                                        <b>' . $pickup_point["company_name"] . '</b><br />
                                                                                        ' . $pickup_point["address"] . '<br />
                                                                                        ' . ($pickup_point["address2"] ? $pickup_point["address2"] . "<br />" : "") . '
                                                                                        ' . $pickup_point["zipcode"] . ' ' . $pickup_point["city"] . '
                                                                                    </label>
                                                                                </div>
                                                                            ';
                                                                        }
                                                                    }
                                                                    $additional_html .= '
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            ';
                                            
                                            if($this->config->get('shipping_shipmondo_google_api_key'))
                                            {
                                                $additional_html .= '
                                                    <script src="https://maps.googleapis.com/maps/api/js?key=' . $this->config->get('shipping_shipmondo_google_api_key') . '&callback=initMap&libraries=&v=weekly"></script>
                                                    <script type="text/javascript">
                                                    function initMap() {
                                                        var map = new google.maps.Map(document.getElementById("map-' . strtolower($method_info['code']) . '"), {
                                                            minZoom: 11,
                                                            disableDefaultUI: true
                                                        });
                                                        
                                                        var infowindow = new google.maps.InfoWindow();
                                                        
                                                        var markers = [];
                                                        var iconBase = "/image/shipping/shipmondo/";
                                                        var pickup_points = JSON.parse(\'' . json_encode($pickup_points) . '\');
                                                        
                                                        pickup_points.forEach(function(pickup_point, key) {
                                                            var position = { lat: pickup_point.latitude, lng: pickup_point.longitude };
                                                            
                                                            markers.push(position);

                                                            var marker = new google.maps.Marker({
                                                                position,
                                                                map,
                                                                icon: iconBase + "' . strtolower($method_info['carrier']['code']) . '.png",
                                                                title: pickup_point.company_name
                                                            });

                                                            marker.addListener("click", (function(marker) {
                                                                return function() {
                                                                    infowindow.setContent(
                                                                        "<b>" + pickup_point.company_name + "</b><br />" +
                                                                        pickup_point.address + "<br />" +
                                                                        pickup_point.address2 + "<br />" +
                                                                        pickup_point.zipcode + " " + pickup_point.city + "<br /><br />" +
                                                                        "<button id=\"map-" + pickup_point.id + "\" type=\"button\" class=\"btn btn-sm btn-default selectPup\">' . $this->language->get("text_select_pup") . '</button>"
                                                                    );
                                                                    
                                                                    infowindow.open(map, marker);
                                                                }
                                                            })(marker));
                                                        });
                                                        
                                                        var bounds = new google.maps.LatLngBounds();
                                                        
                                                        for (var i = 0; i < markers.length; i++) {
                                                            bounds.extend(markers[i]);
                                                        }
                                                        
                                                        map.fitBounds(bounds);
                                                    }
                                                    
                                                    $("#map-' . strtolower($method_info['code']) . '").on("click", ".selectPup", function()
                                                    {
                                                        $("#pup-" + $(this).attr("id").replace("map-", "")).click();
                                                    });
                                                    </script>
                                                    <style>
                                                    #map-' . strtolower($method_info['code']) . ' {
                                                        height: 350px;
                                                    }
                                                    </style>
                                                    ';
                                            }
                                            $additional_html .= '
                                            <script type="text/javascript">
                                            $(".pickup_points").on("click", "input[type=\"radio\"]", function()
                                            {
                                                var pup_id = $(this).val();
                                                
                                                $.ajax({
                                                    url: "index.php?route=extension/shipping/shipmondo/getPickupPoint&country_code=' . $address['iso_code_2'] . '&carrier=' . $method_info['carrier']['code'] . '&pup_id=" + pup_id,
                                                    dataType: "json",
                                                    success: function(json) {
                                                        if (json["name"]) {
                                                            if (json["address2"]) {
                                                                $("#shipmondo-' . strtolower($method_info['code']) . '").find(".selected_pup").html(json["name"] + "<br />" + json["address"] + "<br />" + json["address2"] + "<br />" + json["zipcode"] + " " + json["city"]);
                                                            } else {
                                                                $("#shipmondo-' . strtolower($method_info['code']) . '").find(".selected_pup").html(json["name"] + "<br />" + json["address"] + "<br />" + json["zipcode"] + " " + json["city"]);
                                                            }
                                                            
                                                            //$("input[name=\"shipmondo_' . strtolower($method_info['code']) . '\"]").val(pup_id);
                                                        } else {
                                                            $("#shipmondo-' . strtolower($method_info['code']) . '").find(".selected_pup").html("' . $this->language->get('text_automatic') . '");
                                                            
                                                            //$("input[name=\"shipmondo_' . strtolower($method_info['code']) . '\"]").val(0);
                                                        }
                                                    }
                                                });
                                                
                                                $("#modal-' . strtolower($method_info['code']) . '").modal("hide");
                                            });
                                            </script>
                                            <script type="text/javascript">
                                            var pickup_point = "' . $this->session->data['pickup_point'] . '";
                                            
                                            if ($("input[name=\"shipping_method\"]:checked").val() == "shipmondo.' . strtolower($method_info['code']) . '") {
                                                $("#shipmondo-' . strtolower($method_info['code']) . '").addClass("active").show();
                                                
                                                if (pickup_point > 0) {
                                                    $("#modal-' . strtolower($method_info['code']) . '").modal("show");
                                                    
                                                    $("#shipmondo-' . strtolower($method_info['code']) . '").find(".pickup_points").find("input[value=" + pickup_point + "]").trigger("click");
                                                }
                                            } else {
                                                $("#shipmondo-' . strtolower($method_info['code']) . '").removeClass("active").hide();
                                            }
                                            
                                            $("input[name=\"shipping_method\"]").on("click", function() {
                                                if ($("input[name=\"shipping_method\"]:checked").val() == "shipmondo.' . strtolower($method_info['code']) . '") {
                                                    $("#shipmondo-' . strtolower($method_info['code']) . '").addClass("active").show();
                                                } else {
                                                    $("#shipmondo-' . strtolower($method_info['code']) . '").removeClass("active").hide();
                                                }
                                            });
                                            </script>
                                            <style>
                                            #shipmondo-' . strtolower($method_info['code']) . ' {
                                                display: inline-block;
                                                visibility: hidden;
                                                max-height: 15px;
                                                overflow: hidden;
                                                margin-left: -5px;
                                                max-width: 0px;
                                            }
                                            #shipmondo-' . strtolower($method_info['code']) . '.active {
                                                display: block !important;
                                                visibility: visible !important;
                                                overflow: visible !important;
                                                margin-left: 0px !important;
                                                padding-top: 10px;
                                                padding-bottom: 10px;
                                                max-width: none !important;
                                                max-height: none !important;
                                            }
                                            .button-pup {
                                                margin-top: 10px;
                                                display: block;
                                            }
                                            #modal-' . strtolower($method_info['code']) . ' .modal-content {
                                                border-radius: 0px;
                                                max-height: calc(100vh - 60px);
                                                display: flex;
                                        		flex-direction: column;
                                            }
                                            #modal-' . strtolower($method_info['code']) . ' .modal-content .modal-header {
                                                border-bottom: 0px;
                                                padding: 30px;
                                                min-height: auto !important;
                                            }
                                            #modal-' . strtolower($method_info['code']) . ' .modal-content .modal-body {
                                                padding: 30px;
                                                height: 100%;
                                    			overflow-y: scroll;
                                            }
                                            #map-' . strtolower($method_info['code']) . ' {
                                                width: calc(100% + 60px);
                                                margin: -30px -30px 0px;
                                            }
                                            #modal-' . strtolower($method_info['code']) . ' .modal-content .modal-body .pickup_points {
                                                margin: 15px -15px -30px;
                                                padding: 30px 30px;
                                                overflow: hidden;
                                            }
                                            #modal-' . strtolower($method_info['code']) . ' .modal-content .modal-body input[type="radio"] {
                                                vertical-align: top;
                                                height: 20px;
                                                width: 20px;
                                                margin-right: 10px;
                                            }
                                            #modal-' . strtolower($method_info['code']) . ' .modal-content .modal-body label {
                                                margin-bottom: 15px;
                                                width: auto;
                                                max-width: 190px;
                                            }
                                            </style>
                                        </div>
                                        ';
                                    }
                                    else
                                    {
                                        $additional_html = '
                                                        <div id="shipmondo-' . strtolower($method_info['code']) . '" style="">
                                                            <b>' . $this->language->get('text_pickup_point') . ':</b><br />
                                                            <span class="selected_pup">' . $this->language->get('text_automatic') . '</span>
                                                            
                                                            <select name="shipmondo_' . strtolower($method_info['code']) . '" class="form-control pickup_point">
                                                                <option value="0" selected="selected">-- ' . $this->language->get('text_select_pup') . ' --</option>
                                                                ';
                                        
                                        foreach($pickup_points as $pickup_point)
                                        {
                                            $additional_html .= '<option value="' . $pickup_point["id"] . '">' . $pickup_point["company_name"] . ', ' . $pickup_point["address"] . ', ' . $pickup_point["zipcode"] . ' ' . $pickup_point["city"] . '</option>';
                                        }
                                            
                                        $additional_html .= '</select>
                                            
                                                            <script type="text/javascript">
                                                            var pickup_point = "' . $this->session->data['pickup_point'] . '";
                                                            
                                                            if ($("input[name=\"shipping_method\"]:checked").val() == "shipmondo.' . strtolower($method_info['code']) . '") {
                                                                $("#shipmondo-' . strtolower($method_info['code']) . '").addClass("active").show();
                                                                
                                                                if (pickup_point > 0) {
                                                                    $("#shipmondo-' . strtolower($method_info['code']) . '").find(".pickup_point").val(pickup_point).trigger("change");
                                                                }
                                                            } else {
                                                                $("#shipmondo-' . strtolower($method_info['code']) . '").removeClass("active").hide();
                                                            }
                                                            
                                                            $("input[name=\"shipping_method\"]").on("click", function() {
                                                                if ($("input[name=\"shipping_method\"]:checked").val() == "shipmondo.' . strtolower($method_info['code']) . '") {
                                                                    $("#shipmondo-' . strtolower($method_info['code']) . '").addClass("active").show();
                                                                    
                                                                    if (pickup_point > 0) {
                                                                        $("#shipmondo-' . strtolower($method_info['code']) . '").find(".pickup_point").val(pickup_point).trigger("change");
                                                                    }
                                                                } else {
                                                                    $("#shipmondo-' . strtolower($method_info['code']) . '").removeClass("active").hide();
                                                                }
                                                            });
                                                            
                                                            $("#shipmondo-' . strtolower($method_info['code']) . ' > .pickup_point").on("change", function() {
                                                                pickup_point = $(this).val();
                                                                
                                                                $.ajax({
                                                                    url: "index.php?route=extension/shipping/shipmondo/getPickupPoint&country_code=' . $address['iso_code_2'] . '&carrier=' . $method_info['carrier']['code'] . '&pup_id=" + $(this).val(),
                                                                    dataType: "json",
                                                                    success: function(json) {
                                                                        if (json["name"]) {
                                                                            if (json["address2"]) {
                                                                                $("#shipmondo-' . strtolower($method_info['code']) . '").find(".selected_pup").html(json["name"] + "<br />" + json["address"] + "<br />" + json["address2"] + "<br />" + json["zipcode"] + " " + json["city"]);
                                                                            } else {
                                                                                $("#shipmondo-' . strtolower($method_info['code']) . '").find(".selected_pup").html(json["name"] + "<br />" + json["address"] + "<br />" + json["zipcode"] + " " + json["city"]);
                                                                            }
                                                                        } else {
                                                                            $("#shipmondo-' . strtolower($method_info['code']) . '").find(".selected_pup").html("' . $this->language->get('text_automatic') . '");
                                                                        }
                                                                    }
                                                                });
                                                            });
                                                            </script>
                                                            <style>
                                                            #shipmondo-' . strtolower($method_info['code']) . ' {
                                                                display: inline-block;
                                                                visibility: hidden;
                                                                max-height: 15px;
                                                                overflow: hidden;
                                                                margin-left: -5px;
                                                                max-width: 0px;
                                                            }
                                                            #shipmondo-' . strtolower($method_info['code']) . '.active {
                                                                display: block !important;
                                                                visibility: visible !important;
                                                                overflow: visible !important;
                                                                margin-left: 0px !important;
                                                                padding-top: 10px;
                                                                padding-bottom: 10px;
                                                                max-width: none !important;
                                                                max-height: none !important;
                                                            }
                                                            #shipmondo-' . strtolower($method_info['code']) . ' select {
                                                                width: 100%;
                                                                margin-top: 10px;
                                                            }
                                                            </style>
                                                        </div>
                                                        ';
                                    }
                                }
                                else
                                {
                                    $additional_html = '
                                    <div id="shipmondo-' . strtolower($method_info['code']) . '" style="">
                                        <b>' . $this->language->get('text_pickup_point') . ':</b><br />
                                        ' . $this->language->get('error_address') . '
                                        
                                        <script type="text/javascript">
                                        if ($("input[name=\"shipping_method\"]:checked").val() == "shipmondo.' . strtolower($method_info['code']) . '") {
                                            $("#shipmondo-' . strtolower($method_info['code']) . '").addClass("active").show();
                                        } else {
                                            $("#shipmondo-' . strtolower($method_info['code']) . '").removeClass("active").hide();
                                        }
                                        
                                        $("input[name=\"shipping_method\"]").on("click", function() {
                                            if ($("input[name=\"shipping_method\"]:checked").val() == "shipmondo.' . strtolower($method_info['code']) . '") {
                                                $("#shipmondo-' . strtolower($method_info['code']) . '").addClass("active").show();
                                            } else {
                                                $("#shipmondo-' . strtolower($method_info['code']) . '").removeClass("active").hide();
                                            }
                                        });
                                        </script>
                                        <style>
                                        #shipmondo-' . strtolower($method_info['code']) . ' {
                                            display: inline-block;
                                            visibility: hidden;
                                            max-height: 15px;
                                            overflow: hidden;
                                            margin-left: -5px;
                                            max-width: 0px;
                                        }
                                        #shipmondo-' . strtolower($method_info['code']) . '.active {
                                            display: block !important;
                                            visibility: visible !important;
                                            overflow: visible !important;
                                            margin-left: 0px !important;
                                            padding-top: 10px;
                                            padding-bottom: 10px;
                                            max-width: none !important;
                                            max-height: none !important;
                                        }
                                        </style>
                                    </div>
                                    ';
                                }
                            }
                            
                            $quote_data[strtolower($method_info['code'])] = [
                                'code'          => 'shipmondo.' . strtolower($method_info['code']),
                                'title'         => $method['name'] . $additional_html,
                                'cost'          => $method['price'],
                                'tax_class_id'  => $method['tax_class_id'],
                                'text'          => $this->currency->format($this->tax->calculate($method['price'], $method['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
                            ];
                        }
                    }
                }
            }
        }
        
        $method_data = [];
        
        if($quote_data)
        {
            $method_data = [
                'code'          => 'shipmondo',
                'title'         => $this->language->get('text_title'),
                'quote'         => $quote_data
            ];
        }
        
        return $method_data;
    }
    
    public function getMethods($country_code)
    {
        $method_data = $this->cache->get(strtolower('shipmondo.methods.' . $country_code));
        
        if(empty($method_data))
        {
            $shipmondo = new Shipmondo($this->config->get('shipping_shipmondo_api_user'), $this->config->get('shipping_shipmondo_api_key'));
            
            $carriers = $shipmondo->getCarriers(['country_code' => $country_code]);
            
            if(!empty($carriers['output']))
            {
                foreach($carriers['output'] as $carrier)
                {
                    $product_data = [];
                    
                    $products = $shipmondo->getProducts(['carrier_code' => $carrier['code'], 'sender_country_code' => $this->config->get('shipping_shipmondo_sender_country_code'), 'receiver_country_code' => $country_code]);
                    
                    if(!empty($products['output']))
                    {
                        foreach($products['output'] as $product)
                        {
                            $product_data[$product['id']] = $product;
                        }
                    }
                    
                    $method_data[$carrier['id']] = $carrier;
                    $method_data[$carrier['id']]['products'] = $product_data;
                }
            }
            
            $this->cache->set(strtolower('shipmondo.methods.' . $country_code), $method_data);
        }
        
        return $method_data;
    }
    
    private function getMethod($country_code, $carrier_id, $product_id)
    {
        $method_data = $this->cache->get(strtolower('shipmondo.method.' . $country_code . '_' . $carrier_id . '_' . $product_id));
        
        if(empty($method_data))
        {
            $methods = $this->getMethods($country_code);
            
            if($methods)
            {
                foreach($methods as $carrier)
                {
                    if($carrier['id'] == $carrier_id)
                    {
                        foreach($carrier['products'] as $product)
                        {
                            if($product['id'] == $product_id)
                            {
                                unset($carrier['products']);
                                
                                $method_data = $product;
                            }
                        }
                    }
                }
            }
            
            $this->cache->set(strtolower('shipmondo.method.' . $country_code . '_' . $carrier_id . '_' . $product_id), $method_data);
        }
        
        return $method_data;
    }
    
    private function getPickupPoints($carrier_code, $address)
    {
        if($address['postcode'])
        {
            $pickup_points = $this->cache->get(strtolower('shipmondo.pup.' . $carrier_code . '_' . $address['iso_code_2'] . '.' . $address['postcode'] . '_' . $address['city'] . '_' . $address['address_1']));
            
            if(empty($pickup_points))
            {
                $shipmondo = new Shipmondo($this->config->get('shipping_shipmondo_api_user'), $this->config->get('shipping_shipmondo_api_key'));
                
                $pups = $shipmondo->getPickupPoints([
                    'carrier_code' => $carrier_code,
                    'country_code' => $address['iso_code_2'],
                    'zipcode' => $address['postcode'],
                    'city' => $address['city'],
                    'address' => $address['address_1'],
                    'quantity' => 16
                ]);
                
                if(!empty($pups['output']))
                {
                    $pickup_points = [];
                    
                    foreach($pups['output'] as $pup)
                    {
                        $pickup_points[] = $pup;
                    }
                }
                
                $this->cache->set(strtolower('shipmondo.pup.' . $carrier_code . '_' . $address['iso_code_2'] . '.' . $address['postcode'] . '_' . $address['city'] . '_' . $address['address_1']), $pickup_points);
            }
            
            return $pickup_points;
        }
        else
        {
            return false;
        }
    }
}