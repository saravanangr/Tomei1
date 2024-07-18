<?php
	/**
	* @author      Saravanan GR
	* @link        https://www.mitrahsoft.com/
	*/
    require "sd-system/config.php";

    function getLastDayOfWeekInWeeks($weeksAgo = 0) {
        // Calculate the number of weeks ago
        $interval = new DateInterval("P{$weeksAgo}W");
        // Get the current date
        $currentDate = new DateTime();
        // Modify the current date to go back $weeksAgo weeks
        $currentDate->sub($interval);
        // Set the current date to the next Saturday
        $currentDate->modify('next Saturday');
    
        $lastDayOfWeek = $currentDate->format('Y-m-d');
    
        return $lastDayOfWeek;
    }
    
    $lastDayOfThisWeek = getLastDayOfWeekInWeeks(0);
// if(  $lastDayOfThisWeek === date('Y-m-d')){
        $getUnsubscribers = $db->run_query(" SELECT 
                ppSD_members.email AS email,
                ppSD_members.email_optout AS date,
                ppSD_members.id AS id,
                'members' AS type,
                ppSD_member_data.email_optout_type AS unsubscribe_type
            FROM 
                ppSD_members 
            JOIN 
                ppSD_member_data ON ppSD_members.id = ppSD_member_data.member_id
            WHERE  
                (ppSD_member_data.email_optout = 1 OR ppSD_member_data.email_optout = 2)
            UNION ALL
            SELECT 
                ppSD_contacts.email AS email,
                ppSD_contacts.email_optout AS date,
                ppSD_contacts.id AS id,
                'contacts' AS type,
                ppSD_contact_data.email_optout_type AS unsubscribe_type
            FROM 
                ppSD_contacts 
            JOIN 
                ppSD_contact_data ON ppSD_contacts.id = ppSD_contact_data.contact_id
            WHERE 
                (ppSD_contact_data.email_optout = 1 OR ppSD_contact_data.email_optout = 2)
            UNION ALL
            SELECT 
                ppSD_account_data.primary_contact_email AS email,
                ppSD_accounts.email_optout AS date,
                ppSD_accounts.id AS id,
                'accounts' AS type,
                ppSD_account_data.email_optout_type AS unsubscribe_type
            FROM 
                ppSD_accounts 
            JOIN 
                ppSD_account_data ON ppSD_accounts.id = ppSD_account_data.account_id
            WHERE 
                (ppSD_account_data.email_optout = 1 OR ppSD_account_data.email_optout = 2)
                    ");

    $recordCount = $getUnsubscribers->rowCount();

    if( $recordCount > 0 ) {

        $getEmail      = array();
        $list = array(
            ['ID', 'Email', 'Unsubscribe DATE', 'Type','Unsubscribe Type' ]
        );
        $getall=[];
        foreach( $getUnsubscribers as  $data ) {
            $getall = array(
                'id' => $data['id'],
                'email' => $data['email'],
                'date' => $data['date'],
                'type'=>$data['type'],
                'unsubscribe_type'=>$data['unsubscribe_type']
            );
            array_push($list, $getall);
        }

            $fp = fopen('collectUnsubscribersDetails-'.date("Y-m-d").'.csv', 'w');
            // Loop through file pointer and a line
            foreach ($list as $key=>$fields) {
                fputcsv($fp, $fields);
            }
            fclose($fp);
            $key_data=$db->get_array("SELECT VALUE FROM ppSD_options WHERE id = 'apikey'");
            $key="api:".$key_data['VALUE'];

            send_email($key);
    }
// }
function send_email($key) {
    
        $user = 'saravanan.gr@mitrahsoft.com,prasanna.m@mitrahsoft.com';
   
        $content = array();
        $message = '<!DOCTYPE html><html>
        <head><title></title></head>
        <body><label>Hi saravanan,</lable>
        <p>The following are a list of unsubscribers who are unsubscribed in past 7 day notifications.</p><p>Thanks, <br />Naacos Team</p></body></html>';
        echo"<pre>";
        $fname = dirname(__FILE__).'/collectUnsubscribersDetails-'.date("Y-m-d").'.csv';
        $content = array(
            'from'      => 'support@naacos.com',
            'to'        => $user,
            'subject'   => 'List of Weekly Unsubscriber User Details',
            'html'     => $message,
            'attachment[1]' => curl_file_create($fname, 'application/pdf')
        );
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/mg.naacos.com/messages');
        curl_setopt ($ch, CURLOPT_USERPWD, 'api:key-9b88fcfd0a221fe21da10a1bd6efaade');
        curl_setopt ($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $content );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
}





















?>