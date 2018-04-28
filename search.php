<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">


<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <link rel="stylesheet" type="text/css" href="style.css">
  <title>Search Engine-Search</title>
</head>
<body style ="background-color:#d6d6c2;" >
  <i style="color:#3d3d29;">By Kapetanakis Fanourios</i>
   <center>
   <h1>Search Engine</h1> 
  <form method="get" action="<?php echo $_SERVER['PHP_SELF'];?>">
   <input type="text" name="k" size="40"  />
    
     <b>Sort by</b>
      <select name="by">
   <option value="">None</option>
  <option value="ArticleID">Id</option>
  <option value="ArticleTitle">Title</option>
  <option value="ArticleBody">Body</option>
  <option value="ArticlePublishDate">PublishDate</option>
  <option value="ArticleDescription">Description</option>
   <option value="ArticleKeywords">Keywords</option>
    <option value="AuthorFullName">Author</option>
</select>
<select name="sort">
  <option value="">None</option>
  <option value="ASC">Asc</option>
  <option value="DESC">Desc</option>
  </select>
 <input type="submit" value="Search" />
    </form>
     </center>
    <hr/>
    <?php
      
      $k = $_REQUEST['k'];
      $by = $_REQUEST['by'];
      $sort = $_REQUEST['sort'];
      $page = $_REQUEST['page'];
      $word = $_REQUEST['word'];
      $hypernull = $_REQUEST['hypernull'];
      $hyponull = $_REQUEST['hyponull'];                    
      $synonull = 0;
      

     $terms = explode(" ", $k);
     $num = count($terms);
      ini_set("odbc.default_cursortype", "0");

  $con=odbc_connect("VOS", "dba", "dba");
  if($con) {

     if($k!=""){  
      
      for($i = 0; $i < $num; $i++){
       $synonew1 = [];
       $queryInput = $terms[$i];
                  if(($word!="hypernymys") && ($word!="hyponymys")){
                      $cmd = 'wn ' . ' ' . $queryInput . ' ' . '-synsn';} 
                    if($word == "hypernymys"){
            $hypernull = 2;   
             $cmd = 'wn ' . ' ' . $queryInput . ' -n1 ' . '-hypen';} 
                           
                if($word == "hyponymys"){
             $hyponull = 2;  
           $cmd = 'wn ' . ' ' . $queryInput . ' -n1 ' . '-hypon'; }      
     
    
               $result = shell_exec($cmd); 
    
          preg_match_all('/(?<=\=>\s)(.*)/', $result, $syno); 
          $syno = array_map("unserialize", array_unique(array_map("serialize", $syno))); 
          $synonew = array_reduce($syno, 'array_merge', array()); 
         
         foreach ($synonew as $key => $value) {
           $comma = explode(",", $value);                
           $synonew1 = array_merge((array)$synonew1, (array)$comma); 
             }

           $synonew1 = array_unique($synonew1);   
           $synonew1 = array_values($synonew1);   
             
             foreach ($synonew1 as $key => $value) {
               $value = trim($value);            
             }

              if($synonew1[0]!=""){
              $allarray = array_merge((array)$allarray, (array)$synonew1[0]);  
                                   }
              if($synonew1[1]!=""){                     
              $allarray = array_merge((array)$allarray, (array)$synonew1[1]); 
                                   }
            
              }
             $allarray = array_merge((array)$allarray, (array)$terms);  

             // print_r($allarray);
              $nc = 0;
              $nc = count($allarray);
                 
        
          if($page=="" || $page == "1")
            {$offset = 0;
             
            }
            else {
               $offset = ($page*3)-3;

            }
    $query = "sparql PREFIX dc: <http://www.semanticweb.org/voulitsa/ontologies/2015/11/untitled-ontology-29#> 
    SELECT ?ArticleImage ?ArticlePublishDate ?ArticleTitle ?ArticleDescription ?ArticleBody ?ArticleKeywords ?AuthorFullName ?ArticleSourceLink ?ArticleID
  WHERE {
 ?Article dc:ArticleImage ?ArticleImage .   
 ?Article dc:ArticlePublishDate ?ArticlePublishDate .
 ?Article dc:ArticleTitle ?ArticleTitle .
 ?Article dc:ArticleDescription ?ArticleDescription .
 ?Article dc:ArticleBody ?ArticleBody .
 ?Article dc:ArticleKeywords ?ArticleKeywords .
 ?Article dc:hasAuthor ?Author .
 ?Author dc:AuthorFullName ?AuthorFullName .
 ?Article dc:ArticleSourceLink ?ArticleSourceLink .
 ?Article dc:ArticleID ?ArticleID . 
 FILTER (regex(?ArticlePublishDate,'$allarray[0]','i')||
  regex(?ArticleTitle,'$allarray[0]','i')||
  regex(?ArticleDescription,'$allarray[0]','i')||
  regex(?ArticleBody,'$allarray[0]','i')||
  regex(?ArticleKeywords,'$allarray[0]','i')||
  regex(?AuthorFullName,'$allarray[0]','i')||
  regex(?ArticleID,'$allarray[0]','i')"; 
   if($nc > 1)
      {
         for($m = 1; $m < $nc; $m++){ 
          if($allarray[$m]!="") {
               $query .= "|| regex(?ArticlePublishDate,'$allarray[$m]','i')||
  regex(?ArticleTitle,'$allarray[$m]','i')||
  regex(?ArticleDescription,'$allarray[$m]','i')||
  regex(?ArticleBody,'$allarray[$m]','i')||
  regex(?ArticleKeywords,'$allarray[$m]','i')||
  regex(?AuthorFullName,'$allarray[$m]','i')||
  regex(?ArticleID,'$allarray[$m]','i')";
       }
     }

       } 
     
     if($by !="" && $sort !=""){

     $query .= ")}ORDER BY $sort(?$by) limit 3 OFFSET $offset"; }
       else { $query .= ")}ORDER BY ASC(?ArticleID) limit 3 OFFSET $offset"; }

       
       // echo $query; 
   // print "Executing query [$query]<br />\n";
    $rs = odbc_exec($con, $query);
      

    $err=odbc_errormsg($con);
   // print "Current error state: [$err]<br />\n"; 
            if(($word!="hypernymys") && ($word!="hyponymys")){
            echo "<h4>Results for ".implode(", ",$terms)." and synonymys (".implode(", ",$allarray)."): </h4>";}
            if($word == "hypernymys"){
            echo "<h4>Results for ".implode(", ",$terms)." and hypernymys (".implode(", ",$allarray)."): </h4>";}
            if($word == "hyponymys"){
              echo "<h4>Results for ".implode(", ",$terms)." and hyponymys (".implode(", ",$allarray)."): </h4>";}



    

         $mrs=0;
         while (odbc_fetch_row($rs))
          {
          $articleimage = odbc_result($rs, "ArticleImage");  
          $articlepublishdate = odbc_result($rs,"ArticlePublishDate");
          $articletitle = odbc_result($rs,"ArticleTitle");
          $articledescription = odbc_result($rs,"ArticleDescription");
          $articlebody = odbc_result($rs,"ArticleBody");
          $articlekeywords = odbc_result($rs,"ArticleKeywords");
          $authorfullname = odbc_result($rs,"AuthorFullName");
          $articlesourcelink = odbc_result($rs,"ArticleSourceLink");
          $articleid = odbc_result($rs,"ArticleID");
          
           echo "<h3><b><a href=".$articlesourcelink.">$articletitle</a></b></h3>";
           echo "$articledescription";
           echo "<br><br>";
             
             if(($word!="hypernymys") && ($word!="hyponymys")){
             $synonull=1;} 

             if($word == "hypernymys"){
              $hypernull = 3; }

              if($word == "hyponymys"){ 
                $hyponull = 3;}

           if($page == "" && $mrs == 0)   
           {
                if(($word!="hypernymys") && ($word!="hyponymys")){
           ?>            
          <form method="post" action="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "synonymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>&page=1">
          <input type="submit" name="more" value="Show more results" />
          </form>
          <?php }

            if($word == "hypernymys"){
              ?>            
          <form method="post" action="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "hypernymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>&page=1">
          <input type="submit" name="more" value="Show more results" />
          </form>
          <?php 
            }

            if($word == "hyponymys"){
           ?>            
          <form method="post" action="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "hyponymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>&page=1">
          <input type="submit" name="more" value="Show more results" />
          </form>
          <?php }

          if(isset($_REQUEST['more'])){
           
           }
           else{ break;}
         }
          
           echo "<img src='images/".$articleimage."' width='150' height='100' />";
           echo "<h5>$articlepublishdate</h5>";
           echo "$articlebody";
           echo "<h6>keywords: $articlekeywords</h6>";
           echo "Author: $authorfullname<br>";
           echo "<br>ArticleID: $articleid<br><br><br>";
                 $mrs++;


          }
          $cou = odbc_num_rows($rs);
         
         
           $totalpages = ceil($cou / 3); 
                   echo "<br><br>"; 
               for($j = 1; $j <= $totalpages; $j++)
                   {   
                       if(($word!="hypernymys") && ($word!="hyponymys")){
                     ?><a href="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "synonymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>&page=<?php echo $j; ?>" style="text-decoration:none "><?php echo $j." "; ?></a><?php
                        }
                       if($word == "hypernymys"){
                        ?><a href="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "hypernymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>&page=<?php echo $j; ?>" style="text-decoration:none "><?php echo $j." "; ?></a><?php
                        }
                       if($word == "hyponymys"){
                         ?><a href="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "hyponymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>&page=<?php echo $j; ?>" style="text-decoration:none "><?php echo $j." "; ?></a><?php
                        }

                          
                   }
                     
                  
                 if(($word!="hypernymys") && ($word!="hyponymys") && ($synonull == 0)){ 
                  $hypernull = 1;
                  $hyponull = 1; 
          echo "<b>No results found!</b><br><br>";

              
             ?>                    
            <i>Would you like to</i>
            <a href="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "hypernymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>" class="button">generalize your query using hypernymys</a>
             <i>or</i> 
             <a href="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "hyponymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>" class="button">specialize using hyponymys?</a>   
             <?php  
             
              }
             

           


              if(($hypernull == 2) &&($hyponull == 1)){ 
             echo "<b>No results found!</b><br><br>";

              
             ?>            
             <i>Would you like to</i>
             <a href="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "hyponymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>" class="button">specialize your query using hyponymys?</a>               
             <?php  

              }

              if(($hypernull == 1) && ($hyponull == 2)){  
              
              echo "<b>No results found!</b><br><br>";
              
             ?>            
             <i>Would you like to</i>
             <a href="search.php?k=<?php echo $k; ?>&by=<?php echo $by; ?>&sort=<?php echo $sort; ?>&word=<?php echo "hypernymys"; ?>&hypernull=<?php echo $hypernull; ?>&hyponull=<?php echo $hyponull; ?>" class="button">generalize your query using hypernymys?</a>               
             <?php       

              }

             if(($hypernull == 2) && ($hyponull == 2)){ 

                        echo "<b>Sorry, no results were found for ".implode(", ",$terms).", synonymys, hypernymys or hyponymys!<br>Please try a different search!</b><br><br>";
             }          
           
   }

     
   
    odbc_close($con);
   }
    else {
    print "<p>Failed to connect!</p>\n";
  }

?>
   
   
</body>
</html>
