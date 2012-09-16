<?php
//Connection URL
$myurl = 'http://www.thebigchoice.com/feeds/job_xml.php';

//creates a temp file in system
$fp = tempnam(sys_get_temp_dir(),"XML");
$file = fopen($fp, "w+");

//Database Connectivity
$con = mysql_connect("localhost","root","");
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }
  
mysql_select_db("db_bigchoice", $con);

//curl object to fetch data from url
$curl_handle = curl_init();
curl_setopt ($curl_handle, CURLOPT_URL, $myurl);
curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($curl_handle, CURLOPT_CONNECTTIMEOUT, 1);
curl_setopt($curl_handle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
$xml_data = curl_exec($curl_handle);
curl_close($curl_handle);

//Write curl data into file
fwrite($file,$xml_data);
fclose($file);

//Xml object to read xml data
$xmlDoc = new DOMDocument();
//$xmlDoc->load($xml_data);
$xmlDoc->load($fp);
//$xmlDoc->load("job.xml"); //Manual loading of xml

//Get Records of job
$jobs = $xmlDoc->getElementsByTagName( "Job" );
foreach( $jobs as $job )
{
	$companyRefs = $job->getElementsByTagName( "CompanyRef" );
	$companyRef = $companyRefs->item(0)->nodeValue;
	
    $jobTitles= $job->getElementsByTagName( "JobTitle" );
    $jobTitle= mysql_real_escape_string($jobTitles->item(0)->nodeValue);

    $locations = $job->getElementsByTagName( "SummaryLocation" );
    $location = $locations->item(0)->nodeValue;

	$salaryBenefits = $job->getElementsByTagName( "SalaryBenefits" );
    $salaryBenefit = mysql_real_escape_string($salaryBenefits->item(0)->nodeValue);
	
	$summarys = $job->getElementsByTagName( "Summary" );
    $summary = mysql_real_escape_string(base64_decode($summarys->item(0)->nodeValue));
	
	$descriptions = $job->getElementsByTagName( "Description" );
    $description = mysql_real_escape_string(base64_decode($descriptions->item(0)->nodeValue));
	
	foreach($job->getElementsByTagName( "JobWorkType" ) as $jobworkType)
	{
		$jobType = $jobworkType->getElementsByTagName("WorkTypeID")->item(0)->nodeValue;
	}
	
	$applicationURLs = $job->getElementsByTagName( "ApplicationURL" );
    $applicationURL = mysql_real_escape_string($applicationURLs->item(0)->nodeValue);
	
	$sql="INSERT INTO jobs (CompanyRef, JobTitle, SummaryLocation, SalaryBenefits, Summary, Description, ApplicationURL,WorkTypeID)
VALUES ('$companyRef', '$jobTitle', '$location','$salaryBenefit', '$summary', '$description', '$applicationURL', '$jobType')";

	$result = mysql_query($sql);
	$id_job = mysql_insert_id();
	
	if (!$result){
		echo $sql;
    	die('\nInvalid query: ' . mysql_error());
		break;
	}
	
	//code for jobcategory id
	foreach($job->getElementsByTagName( "JobCategory" ) as $jobCategory)
	{
		$categoryId = $jobCategory->getElementsByTagName("CategoryID")->item(0)->nodeValue;
		$sql="INSERT INTO jobs_category (id_job, id_category) VALUES ('$id_job','$categoryId')";
		$result = mysql_query($sql);
		if (!$result) {
			echo $sql;
    		die('\nInvalid query: ' . mysql_error());
			exit;
		}
	}
	
	//JobRegions
	foreach($job->getElementsByTagName( "JobRegion" ) as $regionType)
	{
		$regionId = $regionType->getElementsByTagName("RegionID")->item(0)->nodeValue;
		$sql="INSERT INTO jobs_region (id_job, id_region) VALUES ('$id_job','$regionId')";
		$result = mysql_query($sql);
		if (!$result) {
			echo $sql;
    		die('\nInvalid query: ' . mysql_error());
			exit;
		}
	}
	mysql_query("COMMIT");
	echo "Record Inserted <br>";
}
mysql_close($con);
?>
