<?php
   include ('dashboard_manager.php');

    $wsdl = "http://".$wdslIP.":9001/OrchestrationService?WSDL";
    $trace = true;
    $exceptions = true;
    error_reporting(E_ALL); ini_set('display_errors', 1);

   try {
        $client = new SoapClient($wsdl, array('trace' => $trace, 'exceptions' => $exceptions));
    }

    //catch exception
    catch(Exception $e) {
        echo "<div class='alert alert-danger' role='alert'>
                <strong>Opps!</strong>&nbsp;Server is offline.
            </div>";  
        exit;       
    }

    $orc_Follows_sql = "SELECT * FROM orcFollowsEdge WHERE orchestrations_id = '".$maxOrcID."'";
    $sqlJob = "SELECT id,jobId,description FROM orcJobs WHERE orchestrations_id = '".$maxOrcID."'";
    $sqlJob2 = "SELECT id,jobId,description FROM orcJobs WHERE orchestrations_id = '".$maxOrcID."'";
    $sqlRule = "SELECT * FROM orcRules WHERE orchestrations_id = '".$maxOrcID."'";
    $sqlLeadsTo = "SELECT * FROM orcLeadsToEdge WHERE orchestrations_id = '".$maxOrcID."'";

    $resultJob = $db->query($sqlJob);
    $resultJob2 = $db->query($sqlJob2);
    $resultFollows = $db->query($orc_Follows_sql);
    $resultLeadsTo = $db->query($sqlLeadsTo);
    $resultRule = $db->query($sqlRule);


    $data = array();
    if ($resultFollows->num_rows > 0) {
        while($rowFollows = $resultFollows->fetch_assoc()) {    
            $data[] = $rowFollows;
        }
    }

    $dataL = array();
    if ($resultLeadsTo->num_rows > 0) {
        while($rowLeads = $resultLeadsTo->fetch_assoc()) {    
            $dataL[] = $rowLeads;
        }
    }


    if ($resultJob2->num_rows > 0) {
        while($row = $resultJob2->fetch_assoc()) {
            $count = 0;
            $jobId = $row['jobId'];
            foreach ($data as $key => $value) {
                if($value["reachingNode"] == $jobId)
                    $count++;
            }
            foreach ($dataL as $key => $value) {
                if($value["reachingNode"] == $jobId)
                    $count++;
            }
        }
    }

    if($count == 0){
        echo "<div class='alert alert-danger' role='alert'>
                <strong>Fail!</strong> There are disconnected node(s).
            </div>";
          echo'<script type="text/javascript">   
    function Redirect() 
    {  
        window.location="orc2.php"; 
    } 
    document.write("You will be redirected to a new page in 5 seconds"); 
    setTimeout(\'Redirect()\', 5000);   
</script>';
          exit(0);
    }




?>

<!-- PAGE BODY -->
    <div class="col-12 mt-5">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Orchestration</h4>
                <div class="alert alert-success" role="alert">
                    <strong>Phew!</strong>Orchestration has been Completed.
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <a href="orc0.php"><button type="Submit" class="btn btn-primary mb-3">Create New Orchestration</button></a>
                        </div>
                        <?php 
                             
                             
                            $orc_sql = "SELECT * FROM orchestrations WHERE orchestrations_id = '".$maxOrcID."'";
                            $resultOrc = $db->query($orc_sql); 


                            if ($resultOrc->num_rows > 0) {
                                while($rowOrc = $resultOrc->fetch_assoc()) {
                                $xml_array['orchestration']['id']          = 1;
                                $xml_array['orchestration']['ownerID']     = $rowOrc['owner'];
                                $xml_array['orchestration']['startJobID']  = $rowOrc['startJobId'];
                            }

                            if(is_null($xml_array['orchestration']['startJobID']))
                            	$xml_array['orchestration']['startJobID'] = 1; // if no startingJob selected, default startjobId: 1

                            }else{
                                echo "0 results";
                            }


                            $count = 0;
                            if ($resultJob->num_rows > 0) {
                                while($row = $resultJob->fetch_assoc()) {
                                    $count = 0;
                                    $sJob = $row["jobId"];
                                    foreach ($data as $key => $value) {
                                        if($value['startingNode'] == $sJob){
                                            $count++;
                                        }
                                    }

                                    if($count == 0){

                                        $sql = "INSERT INTO orcFollowsEdge (startingNode, orchestrations_id, reachingNode, startingNodeType, reachingNodeType)
                                                                     VALUES ('$sJob', '$maxOrcID',0, 'J', 'E')";
                                        $db->query($sql);
                                    }
                                }
                            }

                            /*$let_me_see_sql = "SELECT * FROM orcFollowsEdge WHERE `orchestrations_id` = $maxOrcID";
                            $result_let_me_see = $db->query($let_me_see_sql);
                            if ($result_let_me_see->num_rows > 0) {
                                while($row_let_me_see = $result_let_me_see->fetch_assoc()) {
                                    echo $row_let_me_see['reachingNode']."   ".$row_let_me_see['reachingNodeType']."<br>";
                                }
                            }*/


                             $orc_jobs_sql = "SELECT j.jobId,j.owner,j.messages,j.destination,j.fileUrl,j.relatives , f.startingNode, f.reachingNode, f.reachingNodeType FROM orcJobs j , orcFollowsEdge f  WHERE (j.jobId = f.startingNode and j.orchestrations_id = $maxOrcID and f.orchestrations_id = $maxOrcID)";
                          
                             $resultJobs = $db->query($orc_jobs_sql); 

                             $i = 0;

                             if ($resultJobs->num_rows > 0) {
                                 //echo "Num Rows job: ".$resultJobs->num_rows."<br>";
                                 $idCount = 1;
                                 while($rowJobs = $resultJobs->fetch_assoc()) {
                                     if ($i < $resultJobs->num_rows){
                                     	$ruleId = 0;
                                     	$jobId = 0;
										if($rowJobs['reachingNodeType'] == "J"){
											$jobId = $rowJobs['reachingNode'];
										}
 										if($rowJobs['reachingNodeType'] == "R"){
											$ruleId = $rowJobs['reachingNode'];
										}
						
										$xml_array['jobList'][$i]['id']             = $rowJobs['jobId'];
                                         $xml_array['jobList'][$i]['owner']          = $rowJobs['owner'];
                                         $xml_array['jobList'][$i]['description']    = $rowJobs['messages'];
                                         $xml_array['jobList'][$i]['destination']    = $rowJobs['destination'];
                                         $xml_array['jobList'][$i]['fileUrl']        = $rowJobs['fileUrl'];
                                         $xml_array['jobList'][$i]['relatives']      = $rowJobs['relatives'];
                                         $xml_array['jobList'][$i]['orchFlag']      =  "1";

                                         //echo "dolduruyorum" . $rowJobs['reachingNode']. "<br>";


                                         $xml_array['jobList'][$i]['ruleId'] = $ruleId; 
                                         $xml_array['jobList'][$i]['nextJobId']  = $jobId; 

                                     }
                                    // echo "i = ".$i."<br>";

                                     $i++;
                                     $idCount++;
                                 }
                               //     echo $idCount . "<br>";                         
                             }else{
                                 echo "0 results";
                             }

                             $orc_rules_sql = "SELECT * FROM orcRules r LEFT JOIN (SELECT l.reachingNode, l.startingNode FROM orcLeadsToEdge l LEFT JOIN orcJobs j ON l.reachingNode = j.id) f2 ON r.id = f2.startingNode  WHERE orchestrations_id=$maxOrcID";                     
                          
                             $resultRules = $db->query($orc_rules_sql); 

                             $i = 0;

                             if ($resultRules->num_rows > 0) {
                                // echo "Num Rows rule: ".$resultRules->num_rows."<br>";
                                 $idCount = 1;
                                 while($rowRules = $resultRules->fetch_assoc()) {
                                     if ($i < $resultRules->num_rows){
                                         $xml_array['ruleList'][$i]['id']            = $rowRules['ruleId'];
                                         $xml_array['ruleList'][$i]['ownerID']       = $rowRules['owner'];
                                         $xml_array['ruleList'][$i]['query']         = $rowRules['query'];
                                         
                                         $pivot = $rowRules['ruleId'];

                                         $yesEdgeContent_sql = "SELECT `reachingNode` FROM `orcLeadsToEdge` WHERE `orchestrations_id` = $maxOrcID AND `startingNode` = $pivot AND `yesNoType` = 'yes'";
                                         $resultYesContent = $db->query($yesEdgeContent_sql); 
                                         $rowYesContent = $resultYesContent->fetch_assoc();

                                         $noEdgeContent_sql = "SELECT `reachingNode` FROM `orcLeadsToEdge` WHERE `orchestrations_id` = $maxOrcID AND `startingNode` = $pivot AND `yesNoType` = 'no'";
                                         $resultNoContent = $db->query($noEdgeContent_sql); 
                                        
                                         $rowNoContent = $resultNoContent->fetch_assoc();

                                         $yesEdge = $rowYesContent['reachingNode'];
                                         $noEdge = $rowNoContent['reachingNode'];

                                         if(is_null($yesEdge))
                                            $yesEdge = 0;

                                         if(is_null($noEdge))
                                            $noEdge = 0;

                                         $xml_array['ruleList'][$i]['yesEdge']   = $yesEdge;
                                         $xml_array['ruleList'][$i]['noEdge']    = $noEdge;

                                         echo "this is null: ".NULL;

                                         //echo "Rule id : ".$pivot." YEs : ".$rowYesContent['reachingNode']." NO : ".$rowNoContent['reachingNode'];
                                         //exit;
                                     }
                                    // echo "i = ".$i."<br>";
                                     $i++;
                                     $idCount++;
                                 }

                             }else{
                                 echo "0 results";
                             }


                            
                             $response = $client->addOrchestration($xml_array);
                             echo '<pre>'; print_r($xml_array); echo '</pre>';

                             echo($response->message) . "<br>";
							 
							 
							 print_r($xml_array);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- main content area end -->
