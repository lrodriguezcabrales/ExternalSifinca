<?php

namespace WebPageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use WebPageBundle\Lib\api;


class WebPageController extends Controller
{


 	// public $server = 'http://www.sifinca.net/demoserver/web/app.php/';
  //   public $serverCartagena = 'http://www.sifinca.net/demoserver/web/app.php/';
  //   public $serverMonteria = 'http://www.sifinca.net/demoserver/web/app.php/';

    public $server = 'http://localhost/sifinca/web/app.php/';
    public $serverCartagena = 'http://localhost/sifinca/web/app.php/';
    public $serverMonteria = 'http://localhost/sifinca/web/app.php/';

	public $user= "sifinca@araujoysegovia.com";
	public $pass="araujo123";
	public $token = null;	


    public function indexAction()
    {
        return $this->render('WebPageBundle:Default:index.html.twig');
    }


    public function validateAccess(){
        
        $response = false;

        $data = file_get_contents("/var/www/html/ExternalSifinca/app/config/authentication.json");
        $authentications = json_decode($data, true);

        foreach ($authentications as $key => $aut) {
            foreach ($aut as $k => $a) {
                if($k == 'webpage'){
                    $xtoken = $a['x-token'];
                    $xuser = $k;
                }
            }
        }

        $headers = getallheaders();

        $sentXtoken = null;
        $sentXuser = null;

        foreach ($headers as $key => $h) {

            if($key == 'x-token'){
                $sentXtoken = $h;
            }

            if($key == 'x-user'){
                $sentXuser = $h;
            }
        }


        if(($xtoken == $sentXtoken) && ($xuser == $sentXuser)){

            //Acceso permitido   
            $response = true;
                

        }else{
            //Accesso denegado
            $response = false;
        }

        return $response;

        


    }

    public function createOportunityAction(){
        
        $validateAccess = $this->validateAccess();

        if($validateAccess){

            $request = Request::createFromGlobals();

            $json = $request->getContent();
            $data = json_decode($json,true);

           // print_r($data);
            $oportuTypeProper = $data['property'];
            $oportuTypeProject = $data['project'];

            if(!is_null($oportuTypeProper)){

                //print_r("aqui es property");
                //Separar la letra del numero del inmueble para saber de que ciudad es 
                $string = $data['property'];
                $dividir = explode('-',$string);
                 
                $city = null;
                $carta = null;
                $monte = null;
                foreach ($dividir as $k => $v) {
                  if (preg_match('/([a-zA-Z])([0-9]+)/',$v,$matches)) {
                    $city = $matches[1];// = Letra
                    //$matches[1]; 

                    $consecutive = $matches[2]; // = Numero

                  }
                }


                if(is_null($city)){
                    return new JsonResponse(array('message'=> 'Oportunidad no valida'));
                }

                if($city == 'C'){
                    $carta = ($this->server = $this->serverCartagena);
                }
                if($city == 'M'){
                    $monte = ($this->server = $this->serverMonteria);
                }
            }else{
                if (!is_null($data['project'])) {
                    # code...
                    $consecutive = $oportuTypeProject;
                    //print_r("hola que tal proyecto = ");
                    //print_r($consecutive);


                }
            }


            $oportunityType = $this->getOportunityType($data['oportunityType']);

            $lead = $this->createLead($data);

            $opExist = $this->searchOportunity($lead, $oportunityType);

            if(is_null($opExist)){

                echo "\nCreando nueva oportunidad comercial\n";

                //Crear oportunidad
                $url = $this->server.'crm/main/oportunity';

                $api = $this->SetupApi($url, $this->user, $this->pass);

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

                        //echo "\nEntro aqui1\n";
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

                            //print_r($result2);

                        }
                   
                        if(!is_null($oportuTypeProper)){

                             //print_r($result);
                            //aqui llamar funcion crear requerimiento
                            foreach ($result['data'] as $key => $value) {
                                //print_r($value);
                                $idOportunity = $value['id'];

                                $requirement = $this->createRequirement($idOportunity,$consecutive);

                            }
                        }
                            foreach ($result['data'] as $key => $value) {
                                //print_r($value);
                                $idOportunity = $value['id'];
                                $requirement = $this->createRequirementProject($idOportunity,$consecutive);
                                return new JsonResponse(array('message'=> 'Requerimiento nuevo agregado'));
                            }

                    }
                }
               
                if(is_array($result)){
                                   
                    //return new JsonResponse($result);
                    return new JsonResponse(array());

                }else{
                    return new Response($result);
                }
            }else{
                //return new JsonResponse(array('message'=> 'El cliente ya tiene una oportunidad en curso'));

                //echo "\nActualizando oportunidad\n";
                if(!is_null($oportuTypeProper)){
                    //print_r($opExist);
                    $this->createRequirement($opExist['id'], $consecutive);
                    $this->createOffered($opExist['id'],$consecutive);

                    return new JsonResponse(array('message'=> 'Requerimiento agregado'));
                }

                //cuando es de proyecto
                
                //print_r($opExist['id']);
                $this->createRequirementProject($opExist['id'], $consecutive);
                return new JsonResponse(array('message'=> 'Requerimiento agregado'));


            }

           
        }else{
            return new JsonResponse(array('message'=> 'Accesso denegado'));
        }
        
    }

    /**
     * Crear requerimiento de la oportunidad para property
     */
    public function createRequirement($idOportunity, $consecutive){

        
        $property = $this->searchProperty($consecutive);

        if(!is_null($property)){

            $properties = array();
            $properties[] = $property;

            $bRequirement = array(
                'properties' => $properties
            );

            $url = $this->server.'crm/main/oportunity/save/requirement/'.$idOportunity;

            $api = $this->SetupApi($url, $this->user, $this->pass);

            $result = $api->post($bRequirement);

            $result = json_decode($result, true);

            

            if(isset($result['success'])){
                if($result['success'] == 1 || $result['success'] == true){

                    echo "\n requirement creado aqui\n";

                    $offered = $this->createOffered($idOportunity,$consecutive);
                }
            }else{
                //print_r($result);
            }

        }
    }

    /**
     * Crear requerimiento de la oportunidad de proyecto
     */
    public function createRequirementProject($idOportunity, $consecutive){

        
        $project = $this->searchProject($consecutive);
        // print_r("aqui jjjj -->");
        // print_r($project);

        if(!is_null($project)){

            $properties = array();
            $properties[] = $project;

            $bRequirementProject = array(
                'project' => $properties
            );

            echo "\n".json_encode($bRequirementProject)."\n";
            //print_r($bRequirementProject);

            $url = $this->server.'crm/main/oportunity/save/requirement/'.$idOportunity;

            print_r($url);

            $api = $this->SetupApi($url, $this->user, $this->pass);

            $result = $api->post($bRequirementProject);

            $result = json_decode($result, true);

            

            if(isset($result['success'])){
                if($result['success'] == 1 || $result['success'] == true){

                    echo "\n requirement creado aqui\n";

                    //$offered = $this->createOffered($idOportunity,$consecutive);
                    $comment = $this->createComment($idOportunity,$consecutive);
                }
            }else{
                    print_r($result);
            }

        }
    }



    /**
    Crear ofrecido de la oportunidad
    */
    public function createOffered($idOportunity, $consecutive){

        $property = $this->searchProperty($consecutive);
       
        if(!is_null($property)){

            $properties = array();
            $properties[] = $property;

            $bRequirement = array(
                'id' => $property['id']
            );



            $url = $this->server.'crm/main/oportunity/add/property/offered/'.$idOportunity;

            // echo "\n".$url."\n";
            // print_r(json_encode($bRequirement));
            // print_r(json_encode($bRequirement));

            $api = $this->SetupApi($url, $this->user, $this->pass);

            $result = $api->post($bRequirement);

            $result = json_decode($result, true);

            

            if(isset($result['success'])){
                if($result['success'] == 1 || $result['success'] == true){

                    $comment = $this->createComment($idOportunity,$consecutive);

                }
            }else{
                //print_r($result);
            }

        }
    }

    /**
    Crear comentario de la oportunidad
    */
    public function createComment($idOportunity, $consecutive){

        
        $property = $this->searchProperty($consecutive);

         if(!is_null($property)){

             $properties = array();
             $properties[] = $property;

            //print_r($properties);

            $bComment = array(
               'comment' => '<p>Esta oportunidad fue creada desde: http://www.araujoysegovia.com</p>',
               'idEntity' => $idOportunity,
               'lastCommentDate' => 'true'
            );

            $url = $this->server.'crm/main/oportunity/comment/'.$idOportunity;
            //print_r(json_decode($bComment));
            //print_r(json_encode($bComment));

            $api = $this->SetupApi($url, $this->user, $this->pass);

            $result = $api->post($bComment);

            $result = json_decode($result, true);
            


            

            if(isset($result['success'])){
                if($result['success'] == 1 || $result['success'] == true){

                    echo "\n Comment creado\n";
                // print_r("hola que tal 2");


                    //$offered = $this->createOffered($idOportunity,$consecutive);
                }
            }else{
                //print_r($result);
            }

        }
    }

    public function createParticipant($idOportunity, $consecutive){

        
        $property = $this->searchProperty($consecutive);

         if(!is_null($property)){

             $properties = array();
             $properties[] = $property;

            //print_r($properties);

            $bParticipant = array(
               // 'properties' => $properties
            );

            $url = $this->server.'crm/main/oportunity/comment/participant/'.$idOportunity;
            //print_r($url);

            $api = $this->SetupApi($url, $this->user, $this->pass);

            $result = $api->post($bParticipant);

            $result = json_decode($result, true);

            

            if(isset($result['success'])){
                if($result['success'] == 1 || $result['success'] == true){

                    echo "\n Participant creado\n";
                 //print_r("hola que tal 3");


                    //$offered = $this->createOffered($idOportunity,$consecutive);
                }
            }else{
                //print_r($result);
            }

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

        echo "\nEntro 5\n";
        $oportunityType = null;

       //echo "\n".$opType."\n";
         
        $filter = array(
                'value' => $opType,
                'operator' => 'equal',
                'property' => 'value'
        );

       // print_r($filter);

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

        //print_r($result);

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


    public function searchProperty($consecutive)
    {
        
        $property = null;

        $property = $this->cleanString($consecutive);

        $filter = array(
                'value' => $property,
                'operator' => 'equal',
                'property' => 'consecutive'
        );

        //print_r($filter);

        $filter = json_encode(array($filter));
    
        $url = $this->server.'catchment/main/property?filter='.$filter;
    
        //echo "\n".$url."\n";

        $api = $this->SetupApi($url, $this->user, $this->pass);
    
        $result = $api->get();
        $result = json_decode($result, true);

        // echo "\nOportunityType\n";
        //print_r($result);

        if($result['total'] > 0){
            $property = $result['data'][0];
        }

        return $property;
    }

    public function searchProject($consecutive)
    {
        
        $project = null;

        $project = $this->cleanString($consecutive);

        $filter = array(
                'value' => $project,
                'operator' => 'equal',
                'property' => 'code'
        );

        //print_r($filter);

        $filter = json_encode(array($filter));
    
        $url = $this->server.'project/main/project?filter='.$filter;
    
        //echo "\n".$url."\n";

        $api = $this->SetupApi($url, $this->user, $this->pass);
    
        $result = $api->get();
        $result = json_decode($result, true);

        // echo "\nOportunityType\n";
        //print_r($result);

        if($result['total'] > 0){
            $project = $result['data'][0];
        }

        return $project;
    }

    /**
     * Buscar si el cliente tiene una oportunidad en curso
     */
    public function searchOportunity($lead, $opType)
    {
        
        //print_r($lead);

        $oportunity = null;

        $fl = array(
            'value' => $lead['id'],
            'operator' => 'equal',
            'property' => 'lead.id'
        );

        $fs = array(
            'value' => 'E',
            'operator' => 'equal',
            'property' => 'state.value'
        );

        $ft = array(
            'value' => $opType['value'],
            'operator' => 'equal',
            'property' => 'oportunityType.value'
        );

        $filter[] = $fl;
        $filter[] = $fs;
        $filter[] = $ft;

       // print_r($filter);

        $filter = json_encode($filter);
    
        $url = $this->server.'crm/main/oportunity?filter='.$filter;
    
       // echo "\n".$url."\n";

        $api = $this->SetupApi($url, $this->user, $this->pass);
    
        $result = $api->get();
        $result = json_decode($result, true);

        //print_r($result);

        if($result['total'] > 0){
            $oportunity =  $result['data'][0];
        }

        return $oportunity;
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
    
            //      print_r($headers);
    
            $a->set(array('url'=>$urlapi,'headers'=>$headers));
    
            //print_r($a);
    
            return $a;
    
        }else{
            echo "\nToken no valido\n";
        }  
    }


}
