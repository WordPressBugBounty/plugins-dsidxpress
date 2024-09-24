<?php
/*DIV-15200 : This class will update all diverse solution http links to https*/
class dsIdxLinkUpdater {
     public static function UpdateDSLinksToSSL($postContents){        
         $string = $postContents;
         $arr = array('.diversesolutions.com','.dsagentreach.com','.diverse-cdn.com');
         foreach ($arr as &$value) {
             $start = 0 ;
             $found= true;
                 while($found)
                 {
                     $pos = strpos($string,$value,$start);                                              // Find the domain or its subdomain 
                     if(strlen($pos)>0)
                     {
                         $startToOccurence = substr($string,$start,$pos+strlen($value)-$start);                // Crate a sub string from starpoint to domain and perform replace operation     
                         $http_pos = strrpos($startToOccurence,'http://');                              // Find last index of http:// 
                         $domain = substr($startToOccurence,$http_pos,$pos+strlen($value)-$http_pos);   // Get full domain or sub-domain name 
                         //echo "<script type='text/javascript'>console.log('$domain');</script>";
                         $sslDomain = str_replace("http://","https://",$domain);                        // Replace http:// with https:// in domain or sub-domian name
                         $string = str_replace($domain,$sslDomain,$string);                           // Replace newly generated domain or sub-domain name in original string     
                         $start = $pos+strlen($value)+1;                                              // Set start position to last find position and start searching from this poistion,move to step 1
                     }
                     else 
                         $found = false;
                 }
         }
        
       return  $string;
    }
}
?>
