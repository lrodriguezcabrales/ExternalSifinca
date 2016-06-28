<?php

namespace WebPageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WebPageBundle\Lib\api;


class DefaultController extends Controller
{


 	public $server = 'http://localhost/sifinca/web/app.php/';

	public $user= "sifinca@araujoysegovia.com";
	public $pass="araujo123";
	public $token = null;	


    public function indexAction()
    {
        return $this->render('WebPageBundle:Default:index.html.twig');
    }


    public function createOportunityAction(){
        

        $request = Request::createFromGlobals();

        $json = $request->getContent();
        $data = json_decode($json,true);


        //Crear oportunidad
        $url = $this->server.'crm/main/oportunity';

        $api = $this->SetupApi($url, $this->user, $this->pass);

        $lead = $this->createLead($data);

        $oportunityType = $this->getOportunityType($data['oportunityType']);
        $meansOfContact = $this->getMeansOfContact();
        $responsable = $this->getResponsable();

        $office = null;
        if(!is_null($responsable)){
            //print_r($responsable);
            $office = $responsable['office'];
        }

        $bOportunity = array(
            'lead' => $lead,
            'oportunityType' => $oportunityType,
            'meansOfContact' => $meansOfContact,
            'responsable' => $responsable,
            'office' => $office
        );

        //print_r($bOportunity);

        if(is_null($lead)){
            return new JsonResponse(array('message'=> 'Cliente no valido'));
        }

        if(is_null($oportunityType)){
            return new JsonResponse(array('message'=> 'Tipo de oportunidad no valido'));
        }

        if(is_null($meansOfContact)){
            return new JsonResponse(array('message'=> 'Medio de contacto no valido'));
        }

        if(is_null($responsable)){
            return new JsonResponse(array('message'=> 'Responsable no valido'));
        }
        
        //print_r($bOportunity);

        $json = json_encode($bOportunity);


        $result = $api->post($bOportunity);

        // echo "\nresult";
        // echo $result;
        // print_r($result);

        $result = json_decode($result, true);
        
        if(isset($result['success'])){
            if($result['success'] == true){

                echo "\nEntro aqui\n";
                $sCont = $this->searchContador();

                if(!is_null($sCont)){
                    $cont = $sCont['cont'];
                }else{
                    $cont = 0;
                }

                $cont = $cont + 1;

                $bAssignment = array(
                    "cont" => $cont
                );

                if(isset($sCont['id'])){

                    $url = $this->server.'admin/sifinca/assignment/'.$sCont['id'];

                    //echo "\n".$url."\n";

                    $api = $this->SetupApi($url, $this->user, $this->pass);


                    $result2 = $api->put($bAssignment);

                    print_r($result2);

                }
           
            }
        }
       
        if(is_array($result)){
            return new JsonResponse($result);
        }else{
            return new Response($result);
        }
        
    }

    /**
     * Crear cliente potencial
     */
    public function createLead($data)
    {

        $lead = null;

        $sLead = $this->searchLead($data['client']['email']);

        if(is_null($sLead)){
             $bLead = array(
                "firstname" => $data['client']['firstname'],
                "secondname" => $data['client']['secondname'],
                "lastname" => $data['client']['lastname'],
                "secondLastname" => $data['client']['secondLastname'],
                "email" => $data['client']['email']
            );

            $url = $this->server.'crm/main/lead';

            $api = $this->SetupApi($url, $this->user, $this->pass);


            //print_r($bLead);

            $result = $api->post($bLead);


            $result = json_decode($result, true);

            

           // print_r($result);

            if(isset($result['success'])){
                if($result['success'] == 1 || $result['success'] == true){

                    //echo $result['data'][0];

                    $lead = array(
                        'id'=>$result['data'][0]
                    );
                }
            }

        }else{
            //El cliente ya existe
            $lead = $sLead;
        }
      

        return $lead;

    }

    public function searchLead($email)
    {
        
        $lead = null;

        $email = $this->cleanString($email);

        $filter = array(
                'value' => $email,
                'operator' => 'equal',
                'property' => 'email'
        );

        //print_r($filter);

        $filter = json_encode(array($filter));
    
        $url = $this->server.'crm/main/lead?filter='.$filter;
    
        //echo "\n".$url."\n";

        $api = $this->SetupApi($url, $this->user, $this->pass);
    
        $result = $api->get();
        $result = json_decode($result, true);

        // echo "\nOportunityType\n";
        //print_r($result);

        if($result['total'] > 0){
            $lead = $result['data'][0];
        }

        return $lead;
    }


    /**
     * Obtener tipo de oportunidad
     */
    public function getOportunityType($opType)
    {

        //echo "\nEntro 5\n";
        $oportunityType = null;

       //echo "\n".$opType."\n";
         
        $filter = array(
                'value' => $opType,
                'operator' => 'equal',
                'property' => 'value'
        );

        //print_r($filter);

        $filter = json_encode(array($filter));
    
        $url = $this->server.'crm/main/oportunitytype?filter='.$filter;
    
        //echo "\n".$url."\n";

        $api = $this->SetupApi($url, $this->user, $this->pass);
    
        $result = $api->get();
        $result = json_decode($result, true);

        // echo "\nOportunityType\n";
        //print_r($result);

        if($result['total'] > 0){
            $oportunityType = $result['data'][0];
        }

        return $oportunityType;

    }

    /**
     * Obtener medio de contacto
     */
    public function getMeansOfContact()
    {

        $meansOfContact = null;
         
        $filter = array(
                'value' => 'MD',
                'operator' => 'equal',
                'property' => 'value'
        );


        $filter = json_encode(array($filter));
    
        $url = $this->server.'crm/main/meansofcontact?filter='.$filter;
    
        //echo "\n".$url."\n";

        $api = $this->SetupApi($url, $this->user, $this->pass);
    
        $result = $api->get();
        $result = json_decode($result, true);

       

        if($result['total'] > 0){
            $meansOfContact = $result['data'][0];
        }

        return $meansOfContact; 
    }

    /**
     * Obtener el responsable
     */
    public function getResponsable()
    {

        //Asesores Virtuales
        $asesores = array();
        
        $responsable = null;

        $filter = array(
            'value' => 'Asesores Virtuales',
            'operator' => 'equal',
            'property' => 'name'
        );

        $filter = json_encode(array($filter));
    
        $url = $this->server.'admin/sifinca/group?filter='.$filter;
    
        //echo "\n".$url."\n";

        $api = $this->SetupApi($url, $this->user, $this->pass);
    
        $result = $api->get();
        $result = json_decode($result, true);

        //print_r($result);

       

        if($result['total'] > 0){
            $users = $result['data'][0]['users'];

            foreach ($users as $key => $a) {
                $asesores[] = $a['user'];
            }
        }

        $sCont = $this->searchContador();

        if(!is_null($sCont)){
            $cont = $sCont['cont'];
        }else{
            $cont = 0;
        }

        if($cont < count($asesores)){

            $responsable = $asesores[$cont];
            
        }

        if($responsable){

            $url = $this->server.'admin/security/user/'.$responsable['id'];
            //echo "\n".$url."\n";

            $api = $this->SetupApi($url, $this->user, $this->pass);
        
            $result = $api->get();
            $result = json_decode($result, true);


            //print_r($result);

            $responsable = $result;
        }

        return $responsable;

    }


    public function searchContador()
    {
        $cont = null;

        $filter = array(
            'value' => 'ResponsableForOportunity',
            'operator' => 'equal',
            'property' => 'name'
        );

        $filter = json_encode(array($filter));
    
        $url = $this->server.'admin/sifinca/assignment?filter='.$filter;
    
        //echo "\n".$url."\n";

        $api = $this->SetupApi($url, $this->user, $this->pass);
    
        $result = $api->get();
        
        $result = json_decode($result, true);

        if($result['total'] > 0){
            $cont = $result['data'][0];
        }

        return $cont;
    }

    /**
     * Eliminar espacios en blanco seguidos
     * @param unknown $string
     * @return unknown
     */
    function  cleanString($string){
        $string = trim($string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = preg_replace('/\s\s+/', ' ', $string);
        return $string;
    }

    public function login() {
    	 
    	if(is_null($this->token)){
    
    		//echo "\nEntro a login\n";
    
    		$url= $this->server."login";
    		$headers = array(
    				'Accept: application/json',
    				'Content-Type: application/json',
    		);
    		 
    		$a = new api($url, $headers);
    			
            //print_r($a);
    
    		$result = $a->post(array("user"=>$this->user,"password"=>$this->pass));
    		
            //print_r($result);

            $result = json_decode($result, true);
    		 
    		
    
    		//echo "\n".$result['id']."\n";
    
    
    		if(isset($result['code'])){
    			if($result['code'] == 401){
    
    				$this->login();
    			}
    		}else{
    
    			if(isset($result['id'])){
    
    				$this->token = $result['id'];
    			}else{
    				echo "\nError en el login\n";
    				$this->token = null;
    			}
    
    		}
    	}    	 
    }
    
    public function SetupApi($urlapi,$user,$pass){
    
    	$headers = array(
    			'Accept: application/json',
    			'Content-Type: application/json',
    	);
    
    	$a = new api($urlapi, $headers);
    
    	$this->login();
    	 
    	if(!is_null($this->token)){
    
    		$headers = array(
    				'Accept: application/json',
    				'Content-Type: application/json',
    				//'x-sifinca: SessionToken SessionID="56cf041b296351db058b456e", Username="lrodriguez@araujoysegovia.net"'
    				'x-sifinca: SessionToken SessionID="'.$this->token.'", Username="'.$this->user.'"',
    		);
    
    		//     	print_r($headers);
    
    		$a->set(array('url'=>$urlapi,'headers'=>$headers));
    
    		//print_r($a);
    
    		return $a;
    
    	}else{
    		echo "\nToken no valido\n";
    	}  
    }
      


}
