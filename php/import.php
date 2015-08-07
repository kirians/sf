<?php

header('Content-Type: text/html; charset=utf-8');

require_once './conf.php';
require_once './DBFunctions.php';
require_once './helper.php';

// разбор запрса от сервера
if ($_GET['type'] == 'catalog') {
    $mode = $_GET['mode'];

    if (!is_dir(IMPORT_LOGS)) {
        mkdir(IMPORT_LOGS, 0777);
    }

    $log = IMPORT_LOGS . 'log-' . date('d-m-Y', time()) . '.txt';

    $handle = fopen($log, 'a');

    $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
    $log_mess .= "mode = " . $mode . "\n\r";
    fwrite($handle, $log_mess);

    if ($mode == 'checkauth') {
        echo "success\n1c_auth\n" . rand(100000000, 999999999);
        $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
        $log_mess .= "Принял запрос от сервера\n\r";
        fwrite($handle, $log_mess);
    } elseif ($mode == 'init') {
        echo "zip=yes\nfile_limit=500000000";
        $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
        $log_mess .= "Успешная авторизация\n\r";
        fwrite($handle, $log_mess);
    } elseif ($mode == 'file') {

        $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
        $log_mess .= "Началась предача файла\n\r";
        fwrite($handle, $log_mess);

        if (is_dir(IMPORT_FILES . 'import_files')) {
            removeDir(IMPORT_FILES . 'import_files');
        }
        if (file_exists(IMPORT_FILES . 'import.xml')) {
            unlink(IMPORT_FILES . 'import.xml');
        }
        if (file_exists(IMPORT_FILES . 'offers.xml')) {
            unlink(IMPORT_FILES . 'offers.xml');
        }

        $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
        $log_mess .= "Директория /web/uploads/import/ создана\n\rНачалось скачивание архива " . $_GET['filename'] . "\n\r";
        fwrite($handle, $log_mess);

        file_put_contents(IMPORT_FILES . $_GET['filename'], file_get_contents('php://input'));

        $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
        $log_mess .= "Архив " . $_GET['filename'] . " загружен\n\r";
        fwrite($handle, $log_mess);


        $zip = new ZipArchive();
        if ($zip->open(IMPORT_FILES . $_GET['filename']) === true) {
            $zip->extractTo(IMPORT_FILES);
            $zip->close();
            echo "success\n";
            $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
            $log_mess .= "Архив распакован. Файлы успешно сохранены\n\r";
            fwrite($handle, $log_mess);
        } else {
            echo "failure\nАрхива не существует!";
        }
    } elseif ($mode == 'import') {
        $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
        $log_mess .= "Начало импорта товаров\n\r";
        fwrite($handle, $log_mess);


        $file = $_GET['filename'];

        if ($file == 'import.xml' && file_exists(IMPORT_FILES . $file)) {

            $xml = simplexml_load_file(IMPORT_FILES . $file);

            $db = new DBFunctions(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);

            $categories = $db->findAll('shop_categories');

            $properties = $db->findAll('shop_properties');

            $products['products'] = $db->findAll('shop');

            if ($xml->Классификатор->Группы) {
                $categories = saveCategories($categories, $xml, $db);
                $products['categories'] = $categories;
            }

            if ($xml->Классификатор->Свойства) {
                $properties = saveProperties($properties, $xml, $db);
                $products['properties'] = $properties;
            }

            if ($xml->Каталог->Ид) {
                $db->truncate('exchange_info');
                $db->create('exchange_info', array('catalog_id' => trim($xml->Каталог->Ид)));
            }

            if ($xml->Каталог->Товары) {
                $products = saveProducts($products, $xml, $db);
            }

            $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
            $log_mess .= "Импорт товаров прошел нормально\n\r";
            fwrite($handle, $log_mess);

            //$db->disconnect();
        } elseif ($file == 'offers.xml' && file_exists(IMPORT_FILES . $file)) {
            $xml = simplexml_load_file(IMPORT_FILES . $file);

            $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
            $log_mess .= "Импорт предложений\n\r";
            fwrite($handle, $log_mess);

            $db = new DBFunctions(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);

            $products = $db->findAll('shop');
            $price_types = $db->findAll('shop_price_type');

            if ($products && count($products) > 0) {
                $products['products'] = $products;
                if ($xml->ПакетПредложений->ТипыЦен && $xml->ПакетПредложений->ТипыЦен->ТипЦены) {
                    $price_types = saveShopPriceType($price_types, $xml, $db);
                    $products['price_types'] = $price_types;
                }
            }

            if ($xml->ПакетПредложений->Предложения && $xml->ПакетПредложений->Предложения->Предложение) {
                $products = saveOffers($products, $xml, $db);
            }

            $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
            $log_mess .= "Импорт предложений прошел нормально\n\r";
            fwrite($handle, $log_mess);

            //$db->disconnect();
        }
        echo "success\n";
        $log_mess = "****************** " . date('d-m-Y-H-i-s', time()) . " *********************\n\r";
        $log_mess .= "Импорт полностью завершен\n\r";
        fwrite($handle, $log_mess);
        fclose($handle);
    }
}

// add, update or remove shop categories
function saveCategories($categories, $xml, $db)
{
    $old_remote_ids = $remote_ids = array();

    $isChanged = $xml->Каталог->attributes()->СодержитТолькоИзменения;

    if (count($categories) > 0) {
        foreach ($categories as $category) {

            if (is_null($category['parent_id'])) {
                $old_remote_ids[] = $category['remote_id'];
            }
        }
    }

    if ($xml->Классификатор->Группы->Группа) {
        foreach ($xml->Классификатор->Группы->Группа as $cat) {

            if ($cat->Группы->Группа) {
                foreach ($cat->Группы->Группа as $sub) {

                    $remote_ids[] = trim($sub->Ид);

                    if (!in_array(trim($sub->Ид), $old_remote_ids)) { // insert new category
                        $slug = trim($sub->Наименование);
                        $data = array(
                            'parent_id' => null,
                            'remote_id' => trim($sub->Ид),
                            'name' => addslashes(trim($sub->Наименование)),
                            'slug' => translit($slug)
                        );

                        $result = makeRecord('shop_categories', $db, $data);

                        $new_cat_id = $result['id'];

                        $categories[] = $result;

                        unset($result, $data);

                        // sub_subcategories
                        if ($sub->Группы->Группа) {
                            foreach ($sub->Группы->Группа as $sub_sub) {
                                $remote_ids[] = trim($sub_sub->Ид);

                                $slug = trim($sub_sub->Наименование);

                                $data = array(
                                    'parent_id' => $new_cat_id,
                                    'remote_id' => trim($sub_sub->Ид),
                                    'name' => addslashes(trim($sub_sub->Наименование)),
                                    'slug' => translit($slug)
                                );

                                $result = makeRecord('shop_categories', $db, $data);

                                $new_subcat_id = $result['id'];

                                $categories[] = $result;

                                unset($result, $data);

                                // sub_sub_subcategories
                                if ($sub_sub->Группы->Группа) {
                                    foreach ($sub_sub->Группы->Группа as $sub_sub2) {
                                        $remote_ids[] = trim($sub_sub2->Ид);

                                        $slug = $sub_sub2->Наименование;

                                        $data = array(
                                            'parent_id' => $new_subcat_id,
                                            'remote_id' => trim($sub_sub2->Ид),
                                            'name' => addslashes(trim($sub_sub2->Наименование)),
                                            'slug' => translit($slug)
                                        );

                                        $result = makeRecord('shop_categories', $db, $data);

                                        $categories[] = $result;

                                        unset($result, $data);
                                    }
                                }
                            }
                        }
                    } else {  // update categories
                        $remote_id = trim($sub->Ид);
                        $cat_name = trim($sub->Наименование);

                        foreach ($categories as $key => $category) {
                            if ($category['remote_id'] == $remote_id && $category['name'] != $cat_name) {

                                $db->update('shop_categories', array('name' => $cat_name), array('id' => $category['id']));

                                $categories[$key]['name'] = $cat_name;
                            }


                            if ($remote_id == $category['remote_id'] && $sub->Группы->Группа) {  // update subcategories
                                $tmp = getSubcategory('shop_categories', $db, array('parent_id' => $category['id']));
                                $old_sub_remote_ids = $tmp['remote_id'];
                                $subcategories = $tmp['categories'];
                                unset($tmp);

                                foreach ($sub->Группы->Группа as $sub_sub) {

                                    $sub_name = trim($sub_sub->Наименование);
                                    $sub_remote_id = trim($sub_sub->Ид);

                                    $remote_ids[] = trim($sub_sub->Ид);

                                    if (!in_array($sub_remote_id, $old_sub_remote_ids)) {

                                        $data = array(
                                            'parent_id' => $category['id'],
                                            'remote_id' => $sub_remote_id,
                                            'name' => addslashes($sub_name),
                                            'slug' => translit($sub_name)
                                        );

                                        $result = makeRecord('shop_categories', $db, $data);

                                        $new_subcat_id = $result['id'];

                                        $categories[] = $result;

                                        unset($result, $data);

                                        // sub_sub_subcategories
                                        if ($sub_sub->Группы->Группа) {
                                            foreach ($sub_sub->Группы->Группа as $sub_sub2) {
                                                $remote_ids[] = trim($sub_sub2->Ид);

                                                $slug = trim($sub_sub2->Наименование);

                                                $data = array(
                                                    'parent_id' => $new_subcat_id,
                                                    'remote_id' => trim($sub_sub2->Ид),
                                                    'name' => addslashes(trim($sub_sub2->Наименование)),
                                                    'slug' => translit($slug)
                                                );

                                                $result = makeRecord('shop_categories', $db, $data);

                                                $categories[] = $result;

                                                unset($result, $data);
                                            }
                                        }
                                    } else {
                                        if (count($subcategories) > 0) {
                                            foreach ($subcategories as $k_s => $cat_sub) {
                                                if ($cat_sub['remote_id'] == $sub_remote_id && $cat_sub['name'] != addslashes($sub_name)) {

                                                    $db->update('shop_categories', array('name' => addslashes($sub_name)), array('id' => $cat_sub['id']));

                                                    $categories[$key][$k_s]['name'] = addslashes($sub_name);
                                                }

                                                if ($cat_sub['remote_id'] == $sub_remote_id && $sub_sub->Группы->Группа) {  // update subcategories
                                                    $tmp = getSubcategory('shop_categories', $db, array('parent_id' => $cat_sub['id']));

                                                    $old_sub_sub_remote_ids = $tmp['remote_id'];
                                                    $sub_subcategories = $tmp['categories'];
                                                    unset($tmp);

                                                    foreach ($sub_sub->Группы->Группа as $sub_sub) {
                                                        $sub_sub_remote_id = trim($sub_sub->Ид);
                                                        $sub_sub_name = trim($sub_sub->Наименование);

                                                        $remote_ids[] = $sub_sub_remote_id;

                                                        if (!in_array($sub_sub_remote_id, $old_sub_sub_remote_ids)) {

                                                            $data = array(
                                                                'parent_id' => $cat_sub['id'],
                                                                'remote_id' => $sub_sub_remote_id,
                                                                'name' => addslashes($sub_sub_name),
                                                                'slug' => translit($sub_sub_name)
                                                            );

                                                            $result = makeRecord('shop_categories', $db, $data);

                                                            $categories[] = $result;

                                                            unset($result, $data);
                                                        } else {
                                                            if (count($sub_subcategories) > 0) {
                                                                foreach ($sub_subcategories as $k_s_s => $cat_sub_sub) {
                                                                    if ($cat_sub_sub['remote_id'] == $sub_sub_remote_id && $cat_sub_sub['name'] != addslashes($sub_sub_name)) {

                                                                        $db->update('shop_categories', array('name' => addslashes($sub_sub_name)), array('id' => $cat_sub_sub['id']));

                                                                        $categories[$key][$k_s][$k_s_s]['name'] = addslashes($sub_sub_name);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    /*if (!$isChanged && count($categories) > 0) {
        foreach ($categories as $key => $category) { // remove deleted categories
            if (!in_array($category['remote_id'], $remote_ids)) {
                $db->delete('shop_categories', array('id' => $category['id']));

                unset($categories[$key]);
            }
        }
    }*/

    return $categories;
}

// add, update or remove properties
function saveProperties($properties, $xml, $db)
{
    $old_remote_ids = $new_remote_ids = array();

    $isChanged = $xml->Каталог->attributes()->СодержитТолькоИзменения;

    if (count($properties) > 0) {
        foreach ($properties as $p) {
            $old_remote_ids[] = $p['remote_id'];
        }
    }

    if ($xml->Классификатор->Свойства->Свойство) {
        foreach ($xml->Классификатор->Свойства->Свойство as $prop) {
            $prop_id = trim($prop->Ид);
            $prop_title = trim($prop->Наименование);
            $prop_type = trim($prop->ТипЗначений);
            $new_remote_ids[] = $prop_id;

            if (!in_array($prop->Ид, $old_remote_ids)) {

                $data = array(
                    'remote_id' => $prop_id,
                    'title' => addslashes($prop_title),
                    'type' => addslashes($prop_type)
                );

                $properties[] = makeRecord('shop_properties', $db, $data);
            } else {
                if (count($properties) > 0) {
                    foreach ($properties as $p) {
                        if ($p['remote_id'] == $prop_id) {
                            $data = array();

                            if ($p['title'] != trim($prop->Наименование)) {
                                $data['title'] = addslashes($prop_title);
                            }
                            if ($p['type'] != $prop_type) {
                                $data['type'] = addslashes($prop_type);
                            }
                            if (count($data) > 0) {
                                $db->update('shop_properties', $data, array('id' => $p['id']));
                            }
                        }
                    }
                }
            }
        }
    }


    /*if (!$isChanged && count($properties) > 0) {
        foreach ($properties as $k => $p) {
            if (!in_array($p['remote_id'], $new_remote_ids)) {

                $db->delete('shop_properties', $p['id']);

                unset($properties[$k]);
            }
        }
    }*/

    return $properties;
}

// add, update or remove shop products
function saveProducts($products, $xml, $db)
{
    $old_remote_ids = $new_remote_ids = array();

    $isChanged = $xml->Каталог->attributes()->СодержитТолькоИзменения;

    if (count($products['products']) > 0) {
        foreach ($products['products'] as $p) {
            $old_remote_ids[] = $p['remote_id'];
        }
    }

    $i = 1;

    if ($xml->Каталог->Товары->Товар) {
        foreach ($xml->Каталог->Товары->Товар as $product) {
            $new_remote_ids[] = trim($product->Ид);

            if (!in_array(trim($product->Ид), $old_remote_ids)) {

                $slug = trim($product->Наименование);

                $data = array(
                    'remote_id' => trim($product->Ид),
                    'barcode' => trim($product->Штрихкод),
                    'article' => trim($product->Артикул),
                    'title' => addslashes(trim($product->Наименование)),
                    'unit' => trim($product->БазоваяЕдиница),
                    'description' => addslashes(trim($product->Описание)),
                    'is_active' => 1,
                    'slug' => translit($slug),
                );

                /*if ($product->ПараметрыФильтра && $product->ПараметрыФильтра->Параметр) {
                    foreach ($product->ПараметрыФильтра->Параметр as $param) {
                        if (trim($param->Название) == 'Производитель' && isset($param->Значение)) {
                            $data['vendor'] = trim($param->Значение);
                        }
                        if (trim($param->Название) == 'Тип рукоделия' && isset($param->Значение)) {
                            $data['needlework'] = trim($param->Значение);
                        }
                        if (trim($param->Название) == 'Материал' && isset($param->Значение)) {
                            $data['material'] = trim($param->Значение);
                        }
                        if (trim($param->Название) == 'Размер' && isset($param->Значение)) {
                            $data['size'] = trim($param->Значение);
                        }
                        if (trim($param->Название) == 'Цвет' && isset($param->Значение)) {
                            $data['color'] = trim($param->Значение);
                        }
                        if (trim($param->Название) == 'Тип изделия' && isset($param->Значение)) {
                            $data['product'] = trim($param->Значение);
                        }
                        if (trim($param->Название) == 'Сезон' && isset($param->Значение)) {
                            $data['sizon'] = trim($param->Значение);
                        }
                        if (trim($param->Название) == 'Тема' && isset($param->Значение)) {
                            $data['topic'] = trim($param->Значение);
                        }
                        if (trim($param->Название) == 'Акции и распродажа' && isset($param->Значение)) {
                            $data['is_action'] = trim($param->Значение);
                        }
                    }
                }*/

                $result = makeRecord('shop', $db, $data);

                $new_product_id = $result['id'];

                $products['products'][] = $result;
                $new_remote_ids[] = $result['remote_id'];
                unset($data, $result);

                if ($product->Картинки && $product->Картинки->Картинка) {
                    foreach ($product->Картинки->Картинка as $img) {
                        $image_name_arr = explode('/', $img);
                        $image_name = $image_name_arr[count($image_name_arr) - 1];

                        if (file_exists(IMPORT_FILES . $img)) {
                            rename(IMPORT_FILES . $img, PRODUCT_FILES . $image_name);
                        }
                        $data = array(
                            'product_id' => $new_product_id,
                            'image' => $image_name
                        );

                        $db->create('shop_images', $data);
                        unset($data, $image_name_arr, $image_name);
                    }

                }

                if ($product->Группы) { // add relation product to category
                    foreach ($product->Группы as $cat) {

                        if (isset($products['categories'])) {
                            foreach ($products['categories'] as $c) {
                                if ($c['remote_id'] == trim($cat->Ид)) {
                                    $data = array(
                                        'product_id' => $new_product_id,
                                        'category_id' => $c['id']
                                    );

                                    $db->create('product_to_category', $data);
                                    unset($data);
                                }
                            }
                        }
                    }
                }

                if ($product->ЗначенияСвойств && $product->ЗначенияСвойств->ЗначенияСвойства) { // add relation product to properties
                    foreach ($product->ЗначенияСвойств->ЗначенияСвойства as $property) {
                        if (isset($products['properties'])) {
                            foreach ($products['properties'] as $prop) {
                                if ($prop['remote_id'] == trim($property->Ид)) {
                                    $data = array(
                                        'product_id' => $new_product_id,
                                        'property_id' => $prop['id']
                                    );

                                    $db->create('products_properties', $data);
                                    unset($data);
                                }
                            }
                        }
                    }
                }

                if ($product->ЗначенияРеквизитов && $product->ЗначенияРеквизитов->ЗначениеРеквизита) { // add relation product to props
                    foreach ($product->ЗначенияРеквизитов->ЗначениеРеквизита as $props) {
                        $data = array(
                            'product_id' => $new_product_id,
                            'title' => addslashes(trim($props->Наименование)),
                            'data' => addslashes(trim($props->Значение)),
                        );

                        $db->create('products_props', $data);
                        unset($data);
                    }
                }
                unset($new_product_id);
            } else {
                if (count($products['products']) > 0) { // update product
                    foreach ($products['products'] as $p) {
                        if ($p['remote_id'] == trim($product->Ид)) {
                            $data = array('is_active' => true);

                            if ($p['barcode'] != trim($product->Штрихкод)) {
                                $data['barcode'] = trim($product->Штрихкод);
                            }
                            if ($p['article'] != trim($product->Артикул)) {
                                $data['article'] = trim($product->Артикул);
                            }
                            if ($p['title'] != addslashes(trim($product->Наименование))) {
                                $data['title'] = addslashes(trim($product->Наименование));
                            }
                            if ($p['unit'] != trim($product->БазоваяЕдиница)) {
                                $data['unit'] = trim($product->БазоваяЕдиница);
                            }
                            if ($p['description'] != addslashes(trim($product->Описание))) {
                                $data['description'] = addslashes(trim($product->Описание));
                            }

                            if (count($data) > 0) {
                                $db->update('shop', $data, array('id' => $p['id']));
                                $new_remote_ids[] = $p['remote_id'];
                                unset($data);
                            }

                            if ($product->Картинки && $product->Картинки->Картинка) {
                                $db->delete('shop_images', array('product_id' => $p['id']));

                                foreach ($product->Картинки->Картинка as $img) {
                                    $image_name_arr = explode('/', $img);
                                    $image_name = $image_name_arr[count($image_name_arr) - 1];

                                    if (file_exists(IMPORT_FILES . $img)) {
                                        rename(IMPORT_FILES . $img, PRODUCT_FILES . $image_name);
                                    }
                                    $data = array(
                                        'product_id' => $p['id'],
                                        'image' => $image_name
                                    );

                                    $db->create('shop_images', $data);
                                    unset($data, $image_name_arr, $image_name);
                                }
                            }

                            if ($product->Группы) { // update relation product to category
                                $db->delete('product_to_category', array('product_id' => $p['id']));

                                foreach ($product->Группы as $cat) {

                                    if (isset($products['categories'])) {
                                        foreach ($products['categories'] as $c) {
                                            if ($c['remote_id'] == trim($cat->Ид)) {
                                                $data = array(
                                                    'product_id' => $p['id'],
                                                    'category_id' => $c['id']
                                                );

                                                $db->create('product_to_category', $data);
                                                unset($data);
                                            }
                                        }
                                    }
                                }
                            }

                            if ($product->ЗначенияСвойств && $product->ЗначенияСвойств->ЗначенияСвойства) { // update relation product to properties
                                $db->delete('products_properties', array('product_id' => $p['id']));

                                foreach ($product->ЗначенияСвойств->ЗначенияСвойства as $property) {
                                    if (isset($products['properties'])) {
                                        foreach ($products['properties'] as $prop) {
                                            if ($prop['remote_id'] == trim($property->Ид)) {
                                                $data = array(
                                                    'product_id' => $p['id'],
                                                    'property_id' => $prop['id']
                                                );

                                                $db->create('products_properties', $data);
                                                unset($data);
                                            }
                                        }
                                    }
                                }
                            }

                            if ($product->ЗначенияРеквизитов && $product->ЗначенияРеквизитов->ЗначениеРеквизита) { // update relation product to props
                                $db->delete('products_props', array('product_id' => $p['id']));

                                foreach ($product->ЗначенияРеквизитов->ЗначениеРеквизита as $props) {
                                    $data = array(
                                        'product_id' => $p['id'],
                                        'title' => addslashes(trim($props->Наименование)),
                                        'data' => addslashes(trim($props->Значение)),
                                    );

                                    $db->create('products_props', $data);
                                    unset($data);
                                }
                            }
                        }
                    }
                }
            }

            if ($i % 500 == 0) {
                sleep(5);
                continue;
            }
            $i++;
        }
    }

    if (trim($isChanged) == 'false' && count($products['products']) > 0) {  // deactivate deleted products
        foreach ($products['products'] as $k => $p) {
            if (!in_array($p['remote_id'], $new_remote_ids)) {
                $db->update('shop', array('is_active' => false), array('id' => $p['id']));
                /*$images = $db->findBy('shop_images', array('product_id' => $p['id']));
                if (count($images) > 0) {
                    foreach ($images as $i) {
                        unlink(IMPORT_FILES . $i['image']);
                    }
                }
                $db->delete('shop_images', array('product_id' => $p['id']));

                $db->delete('shop', array('id' => $p['id']));
                unset($products['products'][$k]);*/
            }
        }
    }

    return $products;
}

function saveShopPriceType($price_types, $xml, $db)
{
    $isChanged = $xml->ПакетПредложений->attributes()->СодержитТолькоИзменения;
    $old_type_remote_ids = $new_type_remote_ids = array();

    if (count($price_types) > 0) {
        foreach ($price_types as $type) {
            $old_type_remote_ids[] = $type['remote_id'];
        }
    }

    foreach ($xml->ПакетПредложений->ТипыЦен->ТипЦены as $typePrice) {
        $new_type_remote_ids[] = trim($typePrice->Ид);

        if (!in_array(trim($typePrice->Ид), $old_type_remote_ids)) {
            $data = array(
                'remote_id' => trim($typePrice->Ид),
                'title' => addslashes(trim($typePrice->Наименование)),
                'currency' => addslashes(trim($typePrice->Валюта)),
                'tax_title' => addslashes(trim($typePrice->Налог->Наименование)),
                'tax_value' => addslashes(trim($typePrice->Налог->УчтеноВСумме))
            );

            $result = makeRecord('shop_price_type', $db, $data);

            $price_types[] = $result;
            unset($result, $data);
        } else {
            if (count($price_types) > 0) {
                foreach ($price_types as $type) {
                    $data = array();

                    if ($type['remote_id'] == trim($typePrice->Ид)) {
                        if ($type['title'] != addslashes(trim($typePrice->Наименование))) {
                            $data['title'] = addslashes(trim($typePrice->Наименование));
                        }
                        if ($type['currency'] != addslashes(trim($typePrice->Валюта))) {
                            $data['currency'] = addslashes(trim($typePrice->Валюта));
                        }
                        if ($type['tax_title'] != addslashes(trim($typePrice->Налог->Наименование))) {
                            $data['tax_title'] = addslashes(trim($typePrice->Налог->Наименование));
                        }
                        if ($type['tax_value'] != addslashes(trim($typePrice->Наименование))) {
                            $data['tax_value'] = addslashes(trim($typePrice->Наименование));
                        }
                    }

                    if (count($data) > 0) {
                        $db->update('shop_price_type', $data, array('id' => $type['id']));
                        unset($data);
                    }
                }
            }
        }
    }

    /*if (!$isChanged && count($price_types) > 0) {
        foreach ($price_types as $k => $p) {
            if (!in_array($p['remote_id'], $new_type_remote_ids)) {

                $db->delete('shop_price_type', array('id' => $p['id']));

                unset($price_types[$k]);
            }
        }
    }*/

    return $price_types;
}

function saveOffers($products, $xml, $db)
{
    $i = 1;
    foreach ($products['products'] as $product) {
        foreach ($xml->ПакетПредложений->Предложения->Предложение as $key => $offer) {
            $tmp = explode('#', trim($offer->Ид));
            if ($product['remote_id'] == $tmp[0]) {
                $db->delete('products_price', array('product_id' => $product['id']));
                $db->delete('products_characteristics', array('product_id' => $product['id']));
            }
        }

        foreach ($xml->ПакетПредложений->Предложения->Предложение as $key => $offer) {
            $tmp = explode('#', trim($offer->Ид));
            if ($product['remote_id'] == $tmp[0]) {

                $data = array(
                    'product_id' => $product['id'],
                    'base_unit_code' => addslashes(trim($offer->БазоваяЕдиница->attributes()->Код)),
                    'base_unit_full_name' => addslashes(trim($offer->БазоваяЕдиница->attributes()->НаименованиеПолное)),
                    'base_unit_intl' => addslashes(trim($offer->БазоваяЕдиница->attributes()->МеждународноеСокращение)),
                );

                if ($offer->ХарактеристикиТовара && $offer->ХарактеристикиТовара->ХарактеристикаТовара) {
                    $sql = 'INSERT INTO `products_characteristics` (`product_id`, `char_name`, `char_value`) VALUES ';
                    $valArr = array();
                    foreach ($offer->ХарактеристикиТовара->ХарактеристикаТовара as $char) {
                        if (trim($char->Наименование) == 'Цвет' && isset($char->Значение) && $product['color'] != addslashes(trim($char->Значение))) {
                            //$propsData['color'] = addslashes(trim($char->Значение));
                            $data['char_name'] = addslashes(trim($char->Наименование));
                            $data['char_value'] = addslashes(trim($char->Значение));
                        }
                        $valArr[] = '(' . $product['id'] . ', \'' . addslashes(trim($char->Наименование)) . '\', \'' . addslashes(trim($char->Значение)) . '\')';
                    }

                    $sql .= implode(', ', $valArr);
                    $sql .= ';';

                    $db->query($sql);
                }

                if ($offer->Цены->Цена) {
                    foreach ($offer->Цены->Цена as $price) {
                        $data['view'] = addslashes(trim($price->Представление));
                        $data['price_to_one'] = trim($price->ЦенаЗаЕдиницу);
                        $data['currency'] = addslashes(trim($price->Валюта));
                        $data['unit'] = addslashes(trim($price->Единица));
                        $data['coefficient'] = addslashes(trim($price->Коэффициент));

                        foreach ($products['price_types'] as $pt) {
                            if ($pt['remote_id'] == addslashes(trim($price->ИдТипаЦены))) {
                                $data['price_type'] = $pt['id'];
                            }
                        }

                        if (count($data) > 1) {
                            $db->create('products_price', $data);
                        }
                    }
                }

                unset($data);

                $propsData = array(
                    'barcode' => trim($offer->Штрихкод),
                    //'title' => addslashes(trim($offer->Наименование)),
                    'unit' => addslashes(trim($offer->БазоваяЕдиница)),
                    'quantity' => trim($offer->Количество)
                );

                /*if ($offer->ХарактеристикиТовара && $offer->ХарактеристикиТовара->ХарактеристикаТовара) {
                    foreach ($offer->ХарактеристикиТовара->ХарактеристикаТовара as $char) {

                        if (trim($char->Наименование) == 'Производитель' && isset($char->Значение) && $product['vendor'] != addslashes(trim($char->Значение))) {
                            $propsData['vendor'] = addslashes(trim($char->Значение));
                        }
                        if (trim($char->Наименование) == 'Тип рукоделия' && isset($char->Значение) && $product['needlework'] != addslashes(trim($char->Значение))) {
                            $propsData['needlework'] = addslashes(trim($char->Значение));
                        }
                        if (trim($char->Наименование) == 'Материал' && isset($char->Значение) && $product['material'] != addslashes(trim($char->Значение))) {
                            $propsData['material'] = addslashes(trim($char->Значение));
                        }
                        if (trim($char->Наименование) == 'Размер' && isset($char->Значение) && $product['size'] != addslashes(trim($char->Значение))) {
                            $propsData['size'] = addslashes(trim($char->Значение));
                        }
                        if (trim($char->Наименование) == 'Цвет' && isset($char->Значение) && $product['color'] != addslashes(trim($char->Значение))) {
                            $propsData['color'] = addslashes(trim($char->Значение));
                        }
                        if (trim($char->Наименование) == 'Тип изделия' && isset($char->Значение) && $product['product'] != addslashes(trim($char->Значение))) {
                            $propsData['product'] = addslashes(trim($char->Значение));
                        }
                        if (trim($char->Наименование) == 'Сезон' && isset($char->Значение) && $product['sizon'] != addslashes(trim($char->Значение))) {
                            $propsData['sizon'] = addslashes(trim($char->Значение));
                        }
                        if (trim($char->Наименование) == 'Тема' && isset($char->Значение) && $product['topic'] != addslashes(trim($char->Значение))) {
                            $propsData['topic'] = addslashes(trim($char->Значение));
                        }
                        if (trim($char->Наименование) == 'Акции и распродажа' && isset($char->Значение) && $product['is_action'] != addslashes(trim($char->Значение))) {
                            $propsData['is_action'] = addslashes(trim($char->Значение));
                        }
                    }
                }*/

                $db->update('shop', $propsData, array('id' => $product['id']));
                unset($propsData);
            }
        }
        unset($tmp);
    }

    return $products;
}

function removeDir($dir)
{
    if ($objs = glob($dir . "/*")) {
        foreach ($objs as $obj) {
            is_dir($obj) ? removeDir($obj) : unlink($obj);
        }
    }
    rmdir($dir);
}
