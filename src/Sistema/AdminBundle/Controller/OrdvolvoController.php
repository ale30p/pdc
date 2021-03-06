<?php

namespace Sistema\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\View\TwitterBootstrapView;

use Sistema\AdminBundle\Entity\Ordvolvo;
use Sistema\AdminBundle\Form\OrdvolvoType;
use Sistema\AdminBundle\Form\OrdvolvoFilterType;
use Sistema\AdminBundle\Entity\Solicrep;
use Sistema\AdminBundle\Entity\Consumo;
use Sistema\AdminBundle\Entity\Operaciones;
use Sistema\AdminBundle\Entity\Terceros;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ordvolvo controller.
 *
 * @Route("/ordvolvo")
 */
class OrdvolvoController extends Controller
{
    /**
     * Lists all Ordvolvo entities.
     *
     * @Route("/", name="ordvolvo")
     * @Template()
     */
    public function indexAction()
    {
        list($filterForm, $queryBuilder) = $this->filter();

        list($entities, $pagerHtml) = $this->paginator($queryBuilder);

    
        return array(
            'entities' => $entities,
            'pagerHtml' => $pagerHtml,
            'filterForm' => $filterForm->createView(),
        );
    }

    /**
    * Create filter form and process filter request.
    *
    */
    protected function filter()
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $filterForm = $this->createForm(new OrdvolvoFilterType());
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $em->getRepository('SistemaAdminBundle:Ordvolvo')->createQueryBuilder('e');
    
        // Reset filter
        if ($request->getMethod() == 'POST' && $request->get('filter_action') == 'reset') {
            $session->remove('OrdvolvoControllerFilter');
        }
    
        // Filter action
        if ($request->getMethod() == 'POST' && $request->get('filter_action') == 'filter') {
            // Bind values from the request
            $filterForm->bind($request);

            if ($filterForm->isValid()) {
                // Build the query from the given form object
                $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($filterForm, $queryBuilder);
                // Save filter to session
                $filterData = $filterForm->getData();
                $session->set('OrdvolvoControllerFilter', $filterData);
            }
        } else {
            // Get filter from session
            if ($session->has('OrdvolvoControllerFilter')) {
                $filterData = $session->get('OrdvolvoControllerFilter');
                $filterForm = $this->createForm(new OrdvolvoFilterType(), $filterData);
                $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($filterForm, $queryBuilder);
            }
        }
    
        return array($filterForm, $queryBuilder);
    }

    /**
    * Get results from paginator and get paginator view.
    *
    */
    protected function paginator($queryBuilder)
    {
        // Paginator
        $adapter = new DoctrineORMAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $currentPage = $this->getRequest()->get('page', 1);
        $pagerfanta->setCurrentPage($currentPage);
        $entities = $pagerfanta->getCurrentPageResults();
    
        // Paginator - route generator
        $me = $this;
        $routeGenerator = function($page) use ($me)
        {
            return $me->generateUrl('ordvolvo', array('page' => $page));
        };
    
        // Paginator - view
        $translator = $this->get('translator');
        $view = new TwitterBootstrapView();
        $pagerHtml = $view->render($pagerfanta, $routeGenerator, array(
            'proximity' => 3,
            'prev_message' => $translator->trans('views.index.pagprev', array(), 'JordiLlonchCrudGeneratorBundle'),
            'next_message' => $translator->trans('views.index.pagnext', array(), 'JordiLlonchCrudGeneratorBundle'),
        ));
    
        return array($entities, $pagerHtml);
    }
    
    /**
     * Finds and displays a Ordvolvo entity.
     *
     * @Route("/{id}/show", name="ordvolvo_show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SistemaAdminBundle:Ordvolvo')->find($id);
        
        $consumos=$entity->getConsumos();
        foreach($consumos as $consumo) {               
                $hola=$consumo->getRemitovolvo();
                //var_dump($hola);die();
                $num=$hola->getId();
                //var_dump($num);die();
            }

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ordvolvo entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
            'num'         => $num,
        );
    }

     /**
     * Displays a form to create a new OrdVolvo entity.
     *
     * @Route("/new", name="new_ordvolvo")
     * @Template()
     */
    public function newAction(Request $request)
    {
        $ord = new Ordvolvo();
//        $fec= date("d-m-Y");
//        $ord->setFecha(date("d-m-Y"));
//        $solicitud1 = new Solicrep();
//        $ord->addSolicitudes($solicitud1);
//        $consumo1 = new Consumo();
//        $ord->addConsumos($consumo1);
        
        $data = file_get_contents("https://hb.bbv.com.ar/fnet/mod/inversiones/NL-dolareuro.jsp");
 
//        if ( preg_match('|<td align="right" class="texto2">UF : </td>\s+<td class="texto2"><b>(.*?)</b></td>|is' , $data , $cap ) )
//        {
//        echo "UF ".$cap[1];
//        }
        if ( preg_match('|<td style="text-align: left;">Dolar</td>
<td style="text-align: center;">(.*?)</td>
<td style="text-align: center;">(.*?)</td></tr>|is' , $data , $cap ) )
        {
        $str = $cap[2];
        $fa=str_replace(",", ".",$str);
        }
        
        $form = $this->createForm(new OrdvolvoType(), $ord);
 
        return $this->render('SistemaAdminBundle:Ordvolvo:new.html.twig', array(
            'form' => $form->createView(),
            'dolar'=> $fa
        ));
    }

    /**
     * Creates a new Task entity.
     *
     * @Route("/create", name="ordvolvo_create")
     * @Method("post")
     * @Template("SistemaAdminBundle:Ordvolvo:new.html.twig")
     */
     public function createAction(Request $request)
    { 
        $ord = new Ordvolvo();
        $rem = new \Sistema\AdminBundle\Entity\Remitovolvo();
        $ords = $request->request->get('ordvolvo', array());
        if (isset($ords['solicitudes'])) {
            $solicitudes = $ords['solicitudes'];
            foreach($solicitudes as $solicitud) {                
                $solicitud = new Solicrep();
                $ord->addSolicitudes($solicitud);
            }
        }       
        $sumaNeto=0;
        $cont=0;
        if (isset($ords['consumos'])) {
            $consumos = $ords['consumos'];
            $consumoremito = $ords['consumos'];
            foreach($consumos as $consumo) {
                $sumaNeto= $sumaNeto + $consumo['subtotal'];
                $str = $consumo['subtotal'];
                $fa=str_replace(".", ",",$str);
                $consumo['subtotal']=$fa;                
                $cont=$cont+1;
                
            }
            for ($i = 0; $i <= $cont; $i++) {
                $consumos[$i] = new Consumo();
                //$consumoremito[$i] = new \Sistema\AdminBundle\Entity\Consumoremito();
                $ord->addConsumos($consumos[$i],$rem);
                //$rem->addConsumos($consumoremito[$i]);
            }
        }       
        
        $cont1=0;
        if (isset($ords['operaciones'])) {
            $operaciones = $ords['operaciones'];
//            var_dump($operaciones);die();
            foreach($operaciones as $operacion) {
                $str1 = $operacion['hs'];
                $fa1=str_replace(".", ",",$str1);
                $operacion['hs']=$fa1;   
                $str2 = $operacion['subtotal'];
                $fa2=str_replace(".", ",",$str2);
                $operacion['subtotal']=$fa2;
                $cont1=$cont1+1;
            }
            for ($i = 0; $i <= $cont1; $i++) {
                $operaciones[$i] = new Operaciones();
                $ord->addOperaciones($operaciones[$i]);                
            }
        }
        
        $cont2=0;
        if (isset($ords['terceros'])) {
            $terceros = $ords['terceros'];
            foreach($terceros as $tercero) {
                $str3 = $tercero['unitario'];
                $fa3=str_replace(".", ",",$str3);
                $tercero['unitario']=$fa3;   
                $str4 = $tercero['subtotal'];
                $fa4=str_replace(".", ",",$str4);
                $tercero['subtotal']=$fa4;
                $cont2=$cont2+1;
            }
            for ($i = 0; $i <= $cont2; $i++) {
                $terceros[$i] = new Terceros();
                $ord->addTerceros($terceros[$i]);                
            }
        }     
        
 
        $form = $this->createForm(new OrdvolvoType(), $ord);        
        $form->bindRequest($request);
        
        if ($form->isValid()) {
        $rem->setCliente($ord->getCliente());
        $rem->setChasis($ord->getChasis());
        $rem->setCotizacion($ord->getCotizacion());
        $rem->setDominio($ord->getDominio());
        $rem->setFecha($ord->getFecha());
        $rem->setModelo($ord->getModelo());
        $rem->setNeto($sumaNeto);
        $rem->setEnvia('María Antonella Pescarolo');
        $rem->setConsumos($ord->getConsumos());
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($ord);
            $em->persist($rem);
            $em->flush();
 
            return $this->redirect($this->generateUrl('ordvolvo_show', array('id' => $ord->getId())));   
        }
 
        return array(
            'form' => $form->createView()
        );
    }
    /**
     * Displays a form to edit an existing Ordvolvo entity.
     *
     * @Route("/{id}/edit", name="ordvolvo_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SistemaAdminBundle:Ordvolvo')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ordvolvo entity.');
        }

        $editForm = $this->createForm(new OrdvolvoType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Edits an existing Ordvolvo entity.
     *
     * @Route("/{id}/update", name="ordvolvo_editar")
     * @Method("post")
     * @Template("SistemaAdminBundle:Ordvolvo:edit.html.twig")
     */
    public function editarAction($id, Request $request)
{
    $em = $this->getDoctrine()->getManager();
    $ord = $em->getRepository('SistemaAdminBundle:Ordvolvo')->find($id);
    $deleteForm = $this->createDeleteForm($id);
    
    if (!$ord) {
        throw $this->createNotFoundException('No ord found for is '.$id);
    }
    $originalSolic= array();    
    foreach ($ord->getSolicitudes() as $solicitud) {
        $originalSolic[] = $solicitud;       
    }
    $originalCons= array();    
    foreach ($ord->getConsumos() as $consumo) {
        $originalCons[] = $consumo;       
    }
    $originalOper= array();    
    foreach ($ord->getOperaciones() as $operacion) {
        $originalOper[] = $operacion;       
    }
    $originalTerc= array();    
    foreach ($ord->getTerceros() as $tercero) {
        $originalTerc[] = $tercero;       
    }
    
    $editForm = $this->createForm(new OrdvolvoType(), $ord);
            
            $ords = $request->request->get('ordvolvo', array());
            if (isset($ords['solicitudes'])) {
            $solicitudes = $ords['solicitudes'];            
            $i=0;
            foreach( $originalSolic as $solicitud ) {
                $solicitud->setDescripcion($solicitudes[$i]['descripcion']);               
                $em->persist($solicitud);
                $i++;
            }
            }
            if (isset($ords['operaciones'])) {
            $operaciones = $ords['operaciones'];            
            $i=0;
            foreach( $originalOper as $operacion ) {
                $operacion->setDenominacion($operaciones[$i]['denominacion']);
                $operacion->setHs($operaciones[$i]['hs']);
                $operacion->setSubtotal($operaciones[$i]['subtotal']);
                $em->persist($operacion);
                $i++;
            }
            }
            if (isset($ords['consumos'])) {
            $consumos = $ords['consumos'];            
            $i=0;
            foreach( $originalCons as $consumo ) {
//                $consumo=new Consumo();
                $id1=$consumos[$i]['idRepvolvo'];
                $em1 = $this->getDoctrine()->getManager();
                $rep = $em1->getRepository('SistemaAdminBundle:Repvolvo')->find($id1);
//                var_dump($rep); die;
                $consumo->setOrdvolvo($ord);
                $consumo->setCantidad($consumos[$i]['cantidad']);
                $consumo->setSubtotal($consumos[$i]['subtotal']);
                $consumo->setIdRepvolvo($rep);
                $em->persist($consumo);
                $i++;
            }
            }
            $em->persist($ord);
            $em->flush();
                   

            
            $this->get('session')->getFlashBag()->add('success', 'flash.update.success');

            return $this->redirect($this->generateUrl('ordvolvo_edit', array('id' => $id)));
        
    
    return array(
            'entity'      => $ord,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
        }

    
    /**
     * Deletes a Ordvolvo entity.
     *
     * @Route("/{id}/delete", name="ordvolvo_delete")
     * @Method("post")
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('SistemaAdminBundle:Ordvolvo')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Ordvolvo entity.');
            }

            $em->remove($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'flash.delete.success');
        } else {
            $this->get('session')->getFlashBag()->add('error', 'flash.delete.error');
        }

        return $this->redirect($this->generateUrl('ordvolvo'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
    
     /**
     * Finds and displays a precio Tipo Producto entity.
     *
     * @Route("/repvolvo/precio", name="orden_repvolvo_precio")
     */
    public function retornaPrecioRepvolvo() {
        $isAjax = $this->getRequest()->isXMLHttpRequest();
        if ($isAjax) {
            $id = $this->getRequest()->get('id');
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('SistemaAdminBundle:Repvolvo')->findOneByCodigo($id);
            return new Response($entity->getPrecio());
        }
        return new Response('Error. This is not ajax!', 400);
    }
    
     /**
     * Finds and displays a precio Tipo Producto entity.
     *
     * @Route("/repvolvo/nombre", name="orden_repvolvo_nombre")
     */
    public function retornaNombreRepvolvo() {
        $isAjax = $this->getRequest()->isXMLHttpRequest();
        if ($isAjax) {
            $id = $this->getRequest()->get('id');
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('SistemaAdminBundle:Repvolvo')->findOneByCodigo($id);
            return new Response($entity->getId());
        }
        return new Response('Error. This is not ajax!', 400);
    }
    
    /**
     * @Route("/ajax_member", name="ajax_member")
     */
    public function ajaxMemberAction(Request $request)
    {
        $value = $request->get('term');

        $em = $this->getDoctrine()->getEntityManager();
        $members = $em->getRepository('SistemaAdminBundle:Repvolvo')->findAjaxValue($value);

        $json = array();
        foreach ($members as $member) {
            $json[] = array(
                'label' => $member->getName(),
                'value' => $member->getId()
            );
        }

        $response = new Response();
        $response->setContent(json_encode($json));

        return $response;
    }
    
    /**
     * @Route("/ajax", name="ajax")
     */
    public function ajaxAction(Request $request)
    {
        $value = $request->get('term');

        // .... (Search values)
        $search = array(
            array('value' => 'foo', 'label' => 'Foo'),
            array('value' => 'bar', 'label' => 'Bar')
        );

        $response = new Response();
        $response->setContent(json_encode($search));

        return $response;
    }
    
     /**
     * @Route("/ejemploless", name="ejemplo_less")
     * @Template()
     */
    public function ejemplolessAction()
    {
        return array();
    }
    
        /**
     * REPORTE DE TORNEO GRUPO EQUIPOS
     * 
     * @Route("/{id}/reporte", name="orden_imprimir")
     * @Template()
     */
    public function imprimirOrdenAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SistemaAdminBundle:Ordvolvo')->find($id);        
       
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ordvolvo entity.');
        }
        
        $contenido = $this->renderView('SistemaAdminBundle:Ordvolvo:imprimirOrden.pdf.twig', array(
            'entity'    => $entity,            
        ));

        $pdf = <<<EOD
<style>
table {
    table-layout: fixed;
    width: 100%;
    font-size: 10pt;
}
.table-bordered {
    -moz-border-bottom-colors: none;
    -moz-border-left-colors: none;
    -moz-border-right-colors: none;
    -moz-border-top-colors: none;
    border-collapse: separate;
    border-color: #DDDDDD;
    border-image: none;
    border-radius: 4px;
    border-style: solid;
    border-width: 1px;
}
.table-bordered td {
    border: solid thin #DDDDDD;
}
.table-bordered td.th {
    font-weight: bold;
}
</style>
$contenido
EOD;

        return $this->get('sistema_tcpdf')->quick_pdf($pdf);
    }
    
        /**
     * REPORTE DE TORNEO GRUPO EQUIPOS
     * 
     * @Route("/{id}/remito", name="remito_imprimir")
     * @Template()
     */
    public function imprimirRemitoAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SistemaAdminBundle:Ordvolvo')->find($id);        
       
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Ordvolvo entity.');
        }
        
        $contenido = $this->renderView('SistemaAdminBundle:Ordvolvo:imprimirRemito.pdf.twig', array(
            'entity'    => $entity,            
        ));

        $pdf = <<<EOD
<style>
table {
    table-layout: fixed;
    width: 100%;
    font-size: 10pt;
}
.table-bordered {
    -moz-border-bottom-colors: none;
    -moz-border-left-colors: none;
    -moz-border-right-colors: none;
    -moz-border-top-colors: none;
    border-collapse: separate;
    border-color: #DDDDDD;
    border-image: none;
    border-radius: 4px;
    border-style: solid;
    border-width: 1px;
}
.table-bordered td {
    border: solid thin #DDDDDD;
}
.table-bordered td.th {
    font-weight: bold;
}
</style>
$contenido
EOD;

        return $this->get('sistema_tcpdf')->quick_pdf($pdf);
    }
    
 
}
