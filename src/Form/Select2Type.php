<?php
namespace Openforce\Select2Bundle\Form;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;


class Select2Type extends EntityType
{

    /**
     * @var \Symfony\Component\HttpFoundation\Request;
     */
    private $request;

    private static $field_id_num = 0;

    /**
     * 
     */
    public function __construct($registry, $requestStack)
    {
        self::$field_id_num++;
        $this->request = $requestStack->getCurrentRequest();
        parent::__construct($registry);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
    }

    protected function getFieldKey(array $options)
    {
        $key = $options['class'].self::$field_id_num;
        

        return md5($key);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if($this->request->get("_openforce_select2_request") === $this->getFieldKey($options))
        {
            $this->renderJsonResponse($view->vars['choices'], $options);
        }

        $view->vars['related_fields'] = $options['related_fields'];
        $view->vars['field_key'] = $this->getFieldKey($options);


        $this->createDefaultValues($options, $view->vars['value'], $view->vars['choices']);
    }

    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $class
     */
    protected function createDefaultValues($options, $value, &$choices)
    {
        foreach((array)$value  as $k => $v)
        {
            if(isset($choices[$v]))
            {
                if(is_string($value))
                {
                    return;
                }
                unset($value[$k]);
            }
        }

        $em = $options['em'];
        /** @var \Doctrine\ORM\EntityManager $em */
        $er = $em->getRepository($options['class']);
        $meta = $em->getClassMetadata($options['class']);
        /** @var \Doctrine\ORM\EntityRepository $er */

        $idField = $meta->getIdentifier()[0];
        $qb = $er->createQueryBuilder("a")
            ->where("a.{$idField} in(:ids)")
            ->setParameter("ids", $value);
        
        foreach($qb->getQuery()->getResult() as $v)
        {
            if( !isset($options['choice_label']) or !$options['choice_label'] )
            {
                $label = (string)$v;
            }elseif(is_array($options['choice_label']))
            {
                $label = call_user_func($options['choice_label'], $v);
            }elseif($meta->hasField($options['choice_label']))
            {
                $label = $meta->getFieldValue($v, $options['choice_label']);
            }else{
                $method = $options['choice_label'];
                $label = $v->$method();
            }

            $ids = $meta->getIdentifierValues($v);
            $id = array_shift($ids);
            $choices[$id] = new ChoiceView($v, $id , $label);
        }
    }


    protected function renderJsonResponse($choices, array $options)
    {

        $results = [];
        foreach($choices as $v)
        {
            $results[] = [
                'id' => $v->value,
                'text' => $v->label
            ];
        }
        $more = !(count($results) < $options['max_results']);

        $response = new JsonResponse([
            'results' => $results,
            'pagination' => ['more' => $more ]
            
        ]);
        echo $response->getContent();
        exit;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $request = $this->request;
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'related_fields' => [],
            'field_key' => null,
            'search_field' => null,
            'max_results' => 50,
            'filter' => function(EntityRepository $er, $word, $relatedFields, $options){
                
                $queryBuilder = $er->createQueryBuilder("a");

                if(!$options['search_field'])
                {
                    throw new \Exception("search_field option cannot be null");
                }
                $queryBuilder->andWhere("a.{$options['search_field']} like :word")
                    ->setParameter('word', "%{$word}%")
                    ;
                return $queryBuilder;
            },

        ]);

        $queryBuilderNormalizer = function (Options $options, $queryBuilder) use($request) {
            
            $er = $options['em']->getRepository($options['class']);
            $qb = $options['filter']($er, $request->get("term"), $request->get('related_fields'), $options);
            /** @var QueryBuilder $qb */
            $start = $options['max_results'] * $request->get("page", 1) - $options['max_results'];
            $qb->setFirstResult($start)
                ->setMaxResults($options['max_results']);
            
            return $qb;
        };
        $resolver->setNormalizer('query_builder', $queryBuilderNormalizer);    
        
              

    }

    public function getBlockPrefix()
    {
        return 'openforce_select2';
    }

}