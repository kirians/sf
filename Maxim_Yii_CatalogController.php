<?php

class CatalogController extends Controller {
    
    public function actionIndex() {
        $this->layout = '//layouts/catalog';
	// set seo title
        $this->seoTitle = "Catalog - Golden Wall";
        $returns = array(
            'model' => new ApartmentSearchForm(),
            'count' => 0
        );
        $request = Yii::app()->request;
        
	// create apartment
        $model = new Apartment;
        
        $search = array('owner_active = 1');
        $params = array();
        
	// pull all request parameters for future filtering
        if($request->getParam('best') == '1') {
            $search[] = ' AND best = :best';
            $params['best'] = 1;
        }        
        
        if(is_numeric($request->getParam('obj_type_id'))) {
            $search[] = ' AND obj_type_id = :obj_type_id';
            $params['obj_type_id'] = $request->getParam('obj_type_id');
        }

        if(is_numeric($request->getParam('type'))) {
            $search[] = ' AND type = :type';
            $params['type'] = $request->getParam('type');
        }

        if(is_numeric($request->getParam('land_square_min')) || is_numeric($request->getParam('land_square_max'))) {
            $search[] = ' AND (land_square >= :land_square_min AND land_square <= :land_square_max)';
            $params['land_square_min'] = $request->getParam('land_square_min');
            $params['land_square_max'] = $request->getParam('land_square_max');

        }

        if(is_numeric($request->getParam('square_min')) || is_numeric($request->getParam('square_max'))) {
            $search[] = ' AND (square >= :square_min AND square <= :square_max)';
            $params['square_min'] = $request->getParam('square_min');
            $params['square_max'] = $request->getParam('square_max');
        }

        if(is_numeric($request->getParam('price_min')) || is_numeric($request->getParam('price_max'))) {
            $search[] = ' AND (price >= :price_min AND price <= :price_max)';
            $params['price_min'] = $request->getParam('price_min');
            $params['price_max'] = $request->getParam('price_max');
        }

        if(is_numeric($request->getParam('aren_doh_min')) || is_numeric($request->getParam('aren_doh_max'))) {
            $search[] = ' AND (aren_doh >= :aren_doh_min AND aren_doh <= :aren_doh_max)';
            $params['aren_doh_min'] = $request->getParam('aren_doh_min');
            $params['aren_doh_max'] = $request->getParam('aren_doh_max');
        }
        
        $attr = array('balkon', 'bassein', 'garage', 'lift', 'num_of_rooms', 'parkovka');
        
        foreach($attr as $a) {
            if($request->getParam($a) > '') {
                $search[] = ' AND ' . $a . ' = :' . $a;
                $params[$a] = $request->getParam($a);
            }
        }
        
        if(is_numeric($request->getParam('region_id'))) {
            $search[] = ' AND region = :region_id';
            $params['region_id'] = $request->getParam('region_id');
        }

        if ($request->getParam('coord')) {
            $poly_coordinates = $request->getParam('coord');

            $all_apartments = Apartment::getAllApartments();
            
            $polygon_x = array();
            $polygon_y = array();
            
            if(!empty($poly_coordinates)) {
                foreach($poly_coordinates as $coordinates) {
                    $coord_arr = array();
                    $coordinate = explode('|', $coordinates);

                    $polygon_x[] = $coordinate[0];
                    $polygon_y[] = $coordinate[1];
                }
            }

            $apart_id = array();

            foreach ($all_apartments as $all) {
                $j = count($poly_coordinates) - 1;

                for ($i = 0; $i < count($poly_coordinates); $i++) {

                    if ($polygon_y[$i] < $all->lng && $polygon_y[$j] >= $all->lng || $polygon_y[$j] < $all->lng && $polygon_y[$i] >= $all->lng) {
                        if ($polygon_x[$i] + ($all->lng - $polygon_y[$i]) / ($polygon_y[$j] - $polygon_y[$i]) * ($polygon_x[$j] - $polygon_x[$i]) < $all->lat) {
                            $apart_id[] = $all->id;
                        }
                    }

                    $j = $i;
                }
            }

            if (!empty($apart_id)) {
                $search[] = ' AND ( ';
                for ($k = 0; $k < count($apart_id); $k++) {
                    $search[] = ' id =  :id_' . $k;
                    $params['id_' . $k] = $apart_id[$k];
                    if ($k < count($apart_id) - 1) {
                        $search[] = ' OR ';
                    }
                }
                $search[] = ' ) ';
            }
            
        }
        
        $search = count($search) > 0 ? implode('', $search) : null;
        
	// get items with pagination
        $items = $model->getAllWithPagination(10, $request->getParam('page'), $search, $params, $request->getParam('sort'));
        
        if($request->getParam('sort')) $params['sort'] = $request->getParam('sort');
        
	// check items is exist and move it to params of template
        if($items) {
            $returns['items'] = $items['items'];
            $returns['pages'] = $items['pages'];
            $returns['url'] = '&' . http_build_query($params);
        }
        
        $returns['count'] = $items['pages']->getItemCount();
        // render view
        $this->render('index', $returns);
    }
    
    public function actionView($id) {
	// get current apartment by ID
        $model = Apartment::model()->findByPk($id);
        $this->seoTitle = $model['title_ru']." - Golden Wall";
	// check if apartment is active
        if($model && $model->owner_active == 1 && $model->active == 1) {
            $this->layout = '//layouts/catalog';
            // check user is logged?
            if(Yii::app()->user->getId()) {
                $views = new ApartmentViews();
                $views->apartment_id = Yii::app()->request->getParam('id');
                $views->user_id = Yii::app()->user->getId();
                $views->save();
            }
            // render view
            $this->render('view', array('item' => $model));
        }
        else {
            return $this->redirect('/catalog');
        }
    }
}
