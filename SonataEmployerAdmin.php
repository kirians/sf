<?php
namespace Levelup\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class EmployerAdmin extends Admin
{
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('delete');
        $collection->add('block', $this->getRouterIdParameter().'/block');
    }
    // setup the default sort column and order
    protected $datagridValues = array(
        '_sort_order' => 'ASC',
        '_sort_by' => 'name'
    );

    protected function configureFormFields(FormMapper $formMapper)
    {
        $object = $this->getSubject();
        $fileFieldOptions = array('required' => false);
        if ($object && ($webPath = $object->getImage()))
        {
            $fullPath = $object->getImageUrl();
            $fileFieldOptions['help'] = '<img src="'.$fullPath.'" class="admin-preview" /><input  type="hidden" name="image" value="'.$webPath.'"/><br><div style="line-height:20px;"><input type="checkbox" name="delete_image" id="delete_image"/> <label for="delete_image" style="margin-left:7px;padding-top:5px;display:inline-block;"> Delete Image </label></div>';
            $fileFieldOptions['data_class'] = null;
            $fileFieldOptions['label'] = 'Image';
        }
        $formMapper
            ->with('Case')
         //   ->add('user', null, array('required' => true))
            ->add('name', null, array('required' => true))
            ->add('description', 'textarea', array('required' => true,'attr' => array('class' => '')))
            ->add('country', null, array('required' => true))
            ->add('city', null, array('required' => true))
            ->add('category', null, array('required' => true))
            ->add('user', null, array('required' => true))
            ->add('image', 'file', $fileFieldOptions)
            ->end()
            ->with('Cases')
                ->add('cases', 'sonata_type_collection',
                array('btn_add' => false,'by_reference' => false,'type_options' => array('delete' => false)),
                array(
                    'edit' => 'inline',
                    'inline' => 'table',
                    'admin_code' => 'cases'
                )
            )

            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('country')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name', null, array('label' => 'Company name'))
            ->add('createdAt', 'datetime', array('label' => 'Registration date','format' => 'Y-m-d H:i'))
            ->add('Image', null, array(
                'template' => 'LevelupAdminBundle:Default:image.html.twig',
                'label' => 'Image'))
            ->add('country')
            ->add('_action', 'actions', array(
                'label' => 'Actions',
                'actions' => array(
                    'edit' => array(),
                    'block' => array(
                        'template' => 'LevelupAdminBundle:Employer:list__action_block.html.twig'
                    )
                )
            ))
        ;
    }
    public function prePersist($object) {
        $this->saveFile($object);
    }

    public function preUpdate($object) {
        $this->saveFile($object);
    }

    public function saveFile($object) {
        $saver = $this->getConfigurationPool()->getContainer()->get('sonata.admin.save_image');
        $saver->saveFile($object);
    }

}
