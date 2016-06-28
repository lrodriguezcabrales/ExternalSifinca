<?php
namespace WebPageBundle\Lib;

use Symfony\Component\Yaml\Parser;
use PhpOffice\PhpWord\Settings;

class api {
    
    private $_url ;
    private $_headers;
      
    function __construct ($url,$headers )
    {
        
        $this->_url=$url;
        $this->_headers= $headers;
        
    }
    
     public function set($param){
        
        if(array_key_exists('url',$param) ) {
             $this->_url=$param['url'];
        }
        
        if(array_key_exists('headers',$param) ) {
             $this->_headers=$param['headers'];
        }
    }    
    
    public function get($id=null){
        
        
        
        if(is_null($id)){
           $result=  $this->_callapi('GET', $this->_url, $this->_headers,'');
        }else
            {
             
           $result= $this->_callapi('GET', $this->_url.$id, $this->_headers,'');
        }
        
         
          return $result ;
        
    }
    
 	public function post($data){
        
        // echo "\n\nPOST";

        // echo "\n".$this->_url."\n";
        // print_r($this->_headers);

        // echo "\n".json_encode($data);

        return $this->_callapi('POST', $this->_url, $this->_headers, json_encode($data));
        
        
    }
    
    public function put($data){
    
    	return $this->_callapi('PUT', $this->_url, $this->_headers, json_encode($data));
    
    
    }
   
    
   
    public function postfile($file,$mimetype,$name){
        
        $file_name_with_full_path = realpath($file);
               
          
        $cfile = $this->getCurlValue($file_name_with_full_path,$mimetype,$name);
 

        $data = array('file' => $cfile);
          
       
        //$data = array('extra_info' => '123456','file_contents'=>'@'.$file_name_with_full_path);
                
        $headers = $this->_headers;
        $headers[1]='Content-Type: multipart/form-data';
        
       // print_r($headers);
        return $this->_callapi('POST', $this->_url, $headers, json_encode($data));
                
    }
   
    
    
    public function delete($id=null){
        
        if(is_null($id)){
            
            // traer todos
            $response= $this->get();
         
            $a = json_decode($response,true);
      
           
            foreach($a['data'] as $value){
                            
          
             // print_r( $value['id']);
               $response= $this->_callapi('DELETE', $this->_url.$value['id'], $this->_headers,null);
                
            }
           
                //borrar
            
            
        }else{
               $response= $this->_callapi('DELETE', $this->_url.$id, $this->_headers,null);
     
        }
        
        return $response;
    }
    
    
    
    public function _callapi($method, $url,$headers,$data)
   {
     $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

    switch($method) {
        case 'GET':
            break;
        case 'POST':
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
            break;
        case 'PUT': 
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
            break;
        case 'DELETE':
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }

        $response = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);


        return $response;
   }

    
    
  private  function getCurlValue($filename, $contentType, $postname)
{
   
    if (function_exists('curl_file_create')) {
        return curl_file_create($filename, $contentType, $postname);
    }
 
    // Use the old style if using an older version of PHP
    $value = "@{$this->filename};filename=" . $postname;
    if ($contentType) {
        $value .= ';type=' . $contentType;
    }
 
    return $value;
}
 


   
   
    
    
}