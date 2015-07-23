<?php
namespace TVProgramBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Validator\ErrorElement;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\HttpFoundation\Request;



class AdAdmin extends Admin
{


    // setup the defaut sort column and order
    protected $datagridValues = array(
        '_sort_order' => 'ASC',
        '_sort_by' => 'showTime'
    );
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('clone', $this->getRouterIdParameter().'/clone');
        $collection->add('custom_editable', 'custom_editable');
    }
    protected function configureFormFields(FormMapper $formMapper)
    {
        // get the current Image instance
        $object = $this->getSubject();

        $tvprogram = $this->getConfigurationPool()->getContainer()->getParameter('tvprogram');
        $main_folder = '/';
        if($tvprogram && $tvprogram['image_folder']){
               $main_folder .= $tvprogram['image_folder'];
        }
        // use $fileFieldOptions so we can add other options to the field
        $fileFieldOptions = array('required' => false);
        if ($object && ($webPath = $object->getImage()))
        {
            $image_folder = $object->getShowTime()->format( 'Y-m-d' );
            $fullPath = '/uploads'.$main_folder.'/'.$image_folder.'/'.$webPath;
            $fileFieldOptions['help'] = '<img src="'.$fullPath.'" class="admin-preview" /><input  type="hidden" name="image" value="'.$webPath.'"/><br><div style="line-height:20px;"><input type="checkbox" name="delete_image" id="delete_image"/> <label for="delete_image" style="margin-left:7px;padding-top:5px;display:inline-block;"> Удалить изображения </label></div>';
            $fileFieldOptions['data_class'] = null;
            $fileFieldOptions['label'] = 'Фото';
        }

        $formMapper
            ->with('Редактирование анонса')
            ->add('title', null, array('label' => 'Название','required' => true))
            ->add('subTitle', null, array('label' => 'Подзаголовок','required' => false))
           // ->add('image', 'file', $fileFieldOptions);
            ->add('image', 'file', $fileFieldOptions);
            
        //$formMapper->add('asasa', 'checkbox', array('choices' => array(1 => 'type 1', 2 => 'type 2')));

         $formMapper
             ->add('type', null, array('label' => 'Тип анонса','required' => false))
            ->add('genre', null, array('label' => 'Жанр','required' => false))
            ->add('channel', 'entity', array(
                'class'         => 'TVProgramBundle:Channel',
                'property'      => 'name',
                'label'         => 'Канал',
                'empty_value'   => 'Выберите канал',
                'required' => false
            ))
            ->add('rubric', 'entity', array(
                'class'         => 'TVProgramBundle:Rubric',
                'property'      => 'name',
                'label'         => 'Рубрика',
                'empty_value'   => 'Все',
                'required' => false
            ))
            ->add('city', 'entity', array(
                'class'         => 'TVProgramBundle:City',
                'property'      => 'name',
                'label'         => 'Город',
                'empty_value'   => 'Все',
                'required' => false
            ))
            ->add('content', null, array('label' => 'Анонс'))
            ->add('company', null, array('label' => 'Компания'))
            ->add('production', null, array('label' => 'Производство'))

            ->add('showTime', 'sonata_type_datetime_picker', array(
                 'dp_side_by_side'       => true,
                 'dp_use_current'        => false,
               //  'format'        => "y-M-d",
                 'format'        => "d-M-y HH:mm",
                 'label' => 'Начало передачи',
                 'required' => true))
            ->add('ageLimit', null, array('label' => 'Возврастное ограничение','required' => false))

            ->add('isPublic', 'sonata_type_boolean', array('label' => 'Опубликовать', 'required' => false))
            ->add('isPublicMain', 'sonata_type_boolean', array('label' => 'Опубликовать на главной','required' => false))
            ->add('isLocal', 'sonata_type_boolean', array('label' => 'Местная','required' => false))
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {

        $datagridMapper
            ->add('city',null ,array('label' => 'Город'))
            ->add('channel',null ,array('label' => 'Канал'))
            ->add('rubric',null ,array('label' => 'Рубрика'))
            ->add('show_day', 'doctrine_orm_callback', array(
                'callback' => array($this, 'getDayResult'),
                'label' => 'День'
            ), 'text', array())
            //->add('showTime', 'sonata_type_date_picker', array('input_type' => 'timestamp','label' => 'День'));
        ;
    }
    public function getFilterParameters()
    {
        $default = [];
        if($this->getRequest()->get('channel')){
            $default['channel'] = ['value' => $this->getRequest()->get('channel')];
        }
        else{
            $default['channel'] = ['value' => 1];
        }
        if($this->getRequest()->get('date') && preg_match('/^[0-9]{4}.[0-9]{2}.[0-9]{2}$/', $this->getRequest()->get('date'))){
            $default['show_day'] = ['value' => $day = date('d.m.Y',strtotime($this->getRequest()->get('date')))];
        }
        else{
            $default['show_day'] = ['value' => $day = date('d.m.Y')];
        }
        $this->datagridValues = array_merge($default, $this->datagridValues);

        return parent::getFilterParameters();
    }
    protected function configureListFields(ListMapper $listMapper)
    {
        $rubric_list = $this->getConfigurationPool()->getContainer()->get('doctrine')->getRepository('TVProgramBundle:Rubric')->findAll();
        $listMapper
            ->addIdentifier('title', null, array('label' => 'Название'))
            ->add('showTime', 'datetime', array(
                'label' => 'Начало',
                'format' => 'H:i'
                ))
            ->add('rubric', null, array(
                'template' => 'ApplicationSonataAdminBundle:Ad:rubric.html.twig',
                'label' => 'Рубрика',
                'editable' => true,
                'rubric_list' => $rubric_list
            ))
            ->add('image', null, array(
                'template' => 'ApplicationSonataAdminBundle:Ad:image.html.twig',
                'label' => 'Фото'
            ))
            ->add('isLocal', null, array('label' => 'Местный?','editable' => true))
            ->add('isPublic', null, array('label' => 'Публикуется?','editable' => true))
            ->add('updatedAt', 'datetime', array(
                 'label' => 'Дата редактирования',
                 'format' => 'd.m.Y H:i'))
            ->add('_action', 'actions', array(
                'label' => 'Действия',
                'actions' => array(
                    'edit' => array(),
                    'Clone' => array(
                        'template' => 'SonataAdminBundle:CRUD:list__action_clone.html.twig'
                    ),
                    'delete' => array(),
                )
            ))
        ;
    }

    public function prePersist($ad) {
      $this->saveFile($ad);
    }

    public function preUpdate($ad) {
      $this->saveFile($ad);
    }

    public function saveFile($ad) {
        if($ad->getImage())
        {
            $basepath = $this->getConfigurationPool()->getContainer()->getParameter('upload_dir');
            $tvprogram = $this->getConfigurationPool()->getContainer()->getParameter('tvprogram');
            if($tvprogram && $tvprogram['image_folder']){
                $basepath .= $tvprogram['image_folder'];
                if (!file_exists($basepath)) {
                    if (!mkdir($basepath, 0777)) {
                        die('Не удалось создать директорию...');
                    }
                }
            }

            $basepath .= '/'.$ad->getShowTime()->format( 'Y-m-d' ).'/';

                if (!file_exists($basepath)) {
                    if (!mkdir($basepath, 0777)) {
                        die('Не удалось создать директорию...');
                    }
            }
            $ext_arr = array('gif', 'jpg', 'png', 'bmp');
            $image = $ad->getImage();
            if(is_object($image))
            {
                $info = pathinfo($image->getClientOriginalName());
                if(in_array(strtolower($info['extension']), $ext_arr)) {
                    if($image->move($basepath, $image->getClientOriginalName())) {
                        $info = pathinfo($image->getClientOriginalName());
                        $file = substr(md5($image.time()), 0, 20) . '.' . $info['extension'];
                        rename($basepath . $image->getClientOriginalName(), $basepath . $file);
                        $ad->setImage($file);
                    }
                }
            }
        }
        elseif($this->getRequest()->get('image') && !$this->getRequest()->get('delete_image')){
            $ad->setImage($this->getRequest()->get('image'));
        }
    }
    public function getDayResult($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value'] || $value['value'] && !preg_match('/^[0-9]{2}.[0-9]{2}.[0-9]{4}$/', $value['value'])) {
            $day = date('Y-m-d');
        }
        else{
            $day = date('Y-m-d',strtotime($value['value']));
        }
        //$queryBuilder->andWhere($queryBuilder->expr()->between($queryBuilder->getRootAlias().'.showTime', ':date_from', ':date_to'));
        $queryBuilder->andWhere($queryBuilder->getRootAlias().'.showTime >= :date_from');
        $queryBuilder->andWhere($queryBuilder->getRootAlias().'.showTime <= :date_to');


        $queryBuilder->setParameter('date_from', $day.' 00:00:00');
        $queryBuilder->setParameter('date_to', $day.' 23:59:59');

        return true;
    }
}
