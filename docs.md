COde Example

public function send()
    {
        $msg = 'SMS Success';
        $apikey = 'wpi';
        $sender = '849938';
        $msisdn = '4dojej';

        //api url
        $url = "https://api.automas.com.bd/smsapiv3";

        //passing parameters
        $data = array(
            'msisdn' => $msisdn,
            'smstext' => $msg,
            'apikey' => $apikey,
            'sender' => $sender
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $smsresult = curl_exec($ch);

        //Result
        echo $smsresult;

        //Error Display
        echo curl_error($ch);

    }

API URL (GET & POST) : https://api.automas.com.bd/smsapiv3?apikey=(APIKEY)&sender=(Approved Sender ID)&msisdn=(CONTACT NUMBER)&smstext=(Message Content)
Send message using plain HTTP
Below parameters required before sending message

• Api-Key
• Sender ID
• SMS text
• Type
For Long sms below additional parameter required

• Smsformat
For unicode sms below additional parameter required

Parameter details
1.User validation
Api-Key
70b6d55d7425f0181116aa410cc65569
2.Message
Sender	SMS Text	Type	SMS Format
Dynamic sender ID available in the panel	Message body must be http encoded. For single ASCII sms it must be within 160 characters and for Unicode sms it must be 70 characters. Must be http encoded format	For long sms value will be long and for single sms this parameter not required Example type=long	For Unicode sms use 8 and for ASCII this parameter not required Example:smsformat=8 (for Unicode)
3.Destination
msisdn
Message recipient address Example: msisdn=01870830179 for Bulk SMS msisdn=01870830179,88017XXXXXXXX,….
API Request HTTP
API for single sms :      
https://api.automas.com.bd/smsapiv3?apikey=70b6d55d7425f0181116aa410cc65569&sender=8809617641356&msisdn=01870830179&smstext=SMS Success

API for single sms with Bulk SMS:      
https://api.automas.com.bd/smsapiv3?apikey=70b6d55d7425f0181116aa410cc65569&sender=8809617641356&msisdn=01870830179,88017XXXXXXXX,….&smstext=SMS Success

Send message using Json
Below parameters required before sending message

• Api-Key
• Sender ID
• SMS text
• Type
For Long sms below additional parameter required

• Smsformat
For unicode sms below additional parameter required

Parameter details
1.User validation
Api-Key
70b6d55d7425f0181116aa410cc65569
2.Message
Sender	SMS Text	Type
Dynamic sender ID available in the panel	Message body must be http encoded. For single ASCII sms it must be within 160 characters and for Unicode sms it must be 70 characters. Must be http encoded format	For Unicode sms use 8 and for ASCII this parameter not required Example:smsformat=8 (for Unicode)
3.Destination
msisdn
Message recipient address Example: contacts=01870830179 for Bulk SMS contacts=01870830179,88017XXXXXXXX,....
API Request Json (Single SMS and Bulk SMS)
API URL:      
https://api.automas.com.bd/smsapiv4
Request Body
Request body in json format for single SMS
{
    "api_key" : "70b6d55d7425f0181116aa410cc65569",
    "senderid" : "8809617641356",
    "type" : "text",
    "scheduledDateTime" : "",
    "msg" : "Enter Your Message",
    "contacts" : "01870830179"
}

Request body in json format for Bulk SMS
{
    "api_key" : "70b6d55d7425f0181116aa410cc65569",
    "senderid" : "8809617641356",
    "type" : "text",
    "scheduledDateTime" : "",
    "msg" : "Enter Your Message",
    "contacts" : "01870830179+88017XXXXXXXX+..."
}

API Responses details
Successful response in json format for single SMS and Bulk SMS
status	id	msisdn
It will inform the status whether it is SUCCESS or FAIL.Check Status Code Meaning	For each SMS, system will provide a uniq ID	Client provided MSISDN/CONTACTS will be here.

API Responses
Successful response in json format for single SMS
{
"response":[
    {
        "status":0,
        "id":296334,
        "msisdn":"01870830179"
    }
]
}

Successful response in json format for Bulk SMS
{
"response":[
    {
        "status":0,
        "id":296334,
        "msisdn":"01870830179"
    },
    {
        "status":0,
        "id":296335,
        "msisdn":"88017XXXXXXXX"
    },
    {
        "status":0,
        "id":296336,
        "msisdn":"88018XXXXXXXX"
    }
]
}

API Request Json (Dynamic SMS)
https://api.automas.com.bd/smsapimany
Request Body
Request body in json format for Dynamic SMS
{
     "apikey": "70b6d55d7425f0181116aa410cc65569",
     "sender": "8809617641356",
     "messages": [
         {
             "id": 1,
             "msisdn": "01870830179",
             "smstext": "Enter Your Message"
         },
         {
             "id": 2,
             "msisdn": "88017XXXXXXXX",
             "smstext": "Enter Your Message"
         }
     ]
 }
API Responses details
Successful response in json format for single SMS
status	cid	sid	msisdn
It will inform the status whether it is SUCCESS or FAIL.Check Status Code Meaning	For each SMS request there will be a unique reference ID for Client	For each SMS, system will provide a uniq ID	Client provided MSISDN/CONTACTS will be here.

API Responses
Successful response in json format for Dynamic SMS
{
    "response": [
        {
            "status": 0,
            "cid": 1,
            "sid": 11,
            "msisdn": "01870830179"
        },
        {
            "status": 0,
            "cid": 2,
            "sid": 12,
            "msisdn": "88017XXXXXXXX"
        }
    ]
}
Balance Check API
Parameter details
Api-Key
70b6d55d7425f0181116aa410cc65569
API for Balance Check using Key :      
https://api.automas.com.bd/getbalancev3?apikey=70b6d55d7425f0181116aa410cc65569

API Responses
Successful response in json format for single SMS
{
"response":"xxxx.xx"
}
Status Code Meaning
Status Code	Meaning
0	Success
101	Invalid Message Length
102	Sender Not Valid
103	Authentication Failed
104	Invalid User
105	Invalid MSISDN
106	Incorrect API Key
107	User Account Suspended
108	IP Address Not Allowed
109	API Access Not Allowed
110	Do Not Disturb (DND)
111	Spam Word Detected in Message
1000	Insufficient Balance
2300	Destination Route Issue
2400	Destination Route Not Permitted
3300	System Error
2000,3000,4000	Destination Provider Unavailable
