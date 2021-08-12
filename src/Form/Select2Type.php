<?php
namespace Openforce\Select2Bundle\Form;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
    public function __construct(ManagerRegistry $registry, $requestStack)
    {
        self::$field_id_num++;
        $this->request = $requestStack->getCurrentRequest();
        parent::__construct($registry);
    }

    protected function getFieldKey(array $options)
    {
        $key = $options['class'].self::$field_id_num;
        

        return md5($key);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if($this->request->get("_openforce_select2_request") === $this->getFieldKey($options))
        {
            $this->renderJsonResponse($view->vars['choices'], $options);
        }

        $view->vars['field_key'] = $this->getFieldKey($options);
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
            'field_key' => null,
            'search_field' => null,
            'max_results' => 50,
            'filter' => function(EntityRepository $er, $word, $options){
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
            $qb = $options['filter']($er, $request->get("term"), $options);
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