<?php

$trading_name = auth()->user()->client->name;
$token = auth()->user()->client->key->authorization;

$baseUrl = 'https://tech.fastpayments.com.br//';

$var_json = '{
	"info": {
		"_postman_id": "8b036c9c-0584-4c0e-92d9-23b09b9735ec",
		"name": "FAST PAYMENTS DOCUMENTATION",
		"schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
	},
	"item": [
		{
			"name": "CREATE DEPOSIT",
			"request": {
				"method": "POST",
				"header": [
					{
						"key": "Token",
						"value": "'.$token.'",
						"type": "text"
					},
					{
						"key": "Accept",
						"value": "application/json",
						"type": "text"
					}
				],
				"body": {
					"mode": "raw",
					"raw": "{\r\n    \"method\" : \"pix\",\r\n    \"order_id\" : \"ORDER ID\",\r\n    \"user_id\" : \"USER ID\",\r\n    \"user_name\" : \"Your Name\",\r\n    \"user_document\" : \"00000000\",\r\n    \"user_address\" : \"---\",\r\n    \"user_district\" : \"---\",\r\n    \"user_city\" : \"---\",\r\n    \"user_uf\" : \"---\",\r\n    \"user_cep\" : \"---\",\r\n    \"amount\" : \"1.50\"\r\n}",
					"options": {
						"raw": {
							"language": "json"
						}
					}
				},
				"url": {
					"raw": "'.$baseUrl.'/deposit",
					"host": [
						"'.$baseUrl.'"
					],
					"path": [
						"deposit"
					]
				},
			},
			"response": [
				{
					"name": "CREATE DEPOSIT PIX",
					"originalRequest": {
						"method": "POST",
						"header": [
							{
								"key": "Token",
								"value": "'.$token.'",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\r\n    \"method\" : \"pix\",\r\n    \"order_id\" : \"Your order id\",\r\n    \"user_id\" : \"123456\",\r\n    \"user_name\" : \"Your Name\",\r\n    \"user_document\" : \"99999999999\",\r\n    \"user_address\" : \"Your address\",\r\n    \"user_district\" : \"Your district\",\r\n    \"user_city\" : \"Your city\",\r\n    \"user_uf\" : \"UF\",\r\n    \"user_cep\" : \"99999999\",\r\n    \"amount\" : \"1.80\"\r\n}",
							"options": {
								"raw": {
									"language": "json"
								}
							}
						},
						"url": {
							"raw": "'.$baseUrl.'/deposit",
							"host": [
								"'.$baseUrl.'"
							],
							"path": [
								"deposit"
							]
						}
					},
					"status": "OK",
					"code": 200,
					"_postman_previewlanguage": "json",
					"header": [
						{
							"key": "Content-Type",
							"value": "application/json"
						},
						{
							"key": "Content-Length",
							"value": "425"
						},
						{
							"key": "Connection",
							"value": "keep-alive"
						},
						{
							"key": "Date",
							"value": "Tue, 31 Aug 2021 15:30:03 GMT"
						},
						{
							"key": "x-amzn-RequestId",
							"value": "d5e6d8a6-7650-4844-a5e3-eb30dbe26184"
						},
						{
							"key": "Access-Control-Allow-Origin",
							"value": "*"
						},
						{
							"key": "x-amz-apigw-id",
							"value": "E8CmFEtnCYcFgLA="
						},
						{
							"key": "X-Amzn-Trace-Id",
							"value": "Root=1-612e4af3-73bbe87d07c463ca2b04057e"
						},
						{
							"key": "X-Cache",
							"value": "Miss from cloudfront"
						},
						{
							"key": "Via",
							"value": "1.1 819fb1f29c3038ca3cec04e041a0aa1f.cloudfront.net (CloudFront)"
						},
						{
							"key": "X-Amz-Cf-Pop",
							"value": "GRU1-C1"
						},
						{
							"key": "X-Amz-Cf-Id",
							"value": "YZ-IIL-CNS4tstQyBJOnbl_Tx82_qxJdYdErkv4lgDYVGutvPe2QEw=="
						}
					],
					"cookie": [],
					"body": "{\n    \"order_id\": \"665151251281952\",\n    \"solicitation_date\": \"2022-03-31 12:30:02\",\n    \"due_date\": \"2022-04-04\",\n    \"code_identify\": \"FST01388887\",\n    \"amount\": \"180.20\",\n    \"status\": \"pending\",\n    \"link_qr\": \"https://admin.fastpayments.com.br/qr/498374/123654789158252571/200x200\",\n    \"content_qr\": \"00020101021226510014BR.GOV.BCB.PIX0114381737280001980211A4P0138888753040000530398654041.805802BR5906FastPayments6014Belo Horizonte61083038040362150511A4P013888876304AE68\"\n}"
				},
				{
					"name": "CREATE DEPOSIT INVOICE",
					"originalRequest": {
						"method": "POST",
						"header": [
							{
								"key": "Token",
								"value": "'.$token.'",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\r\n    \"method\" : \"invoice\",\r\n    \"order_id\" : \"Your order id\",\r\n    \"user_id\" : \"123456\",\r\n    \"user_name\" : \"Your Name\",\r\n    \"user_document\" : \"99999999999\",\r\n    \"user_address\" : \"Your address\",\r\n    \"user_district\" : \"Your district\",\r\n    \"user_city\" : \"Your city\",\r\n    \"user_uf\" : \"UF\",\r\n    \"user_cep\" : \"99999999\",\r\n    \"bank_code\" : \"100\",\r\n    \"amount\" : \"20.50\"\r\n}",
							"options": {
								"raw": {
									"language": "json"
								}
							}
						},
						"url": {
							"raw": "'.$baseUrl.'/deposit",
							"host": [
								"'.$baseUrl.'"
							],
							"path": [
								"deposit"
							]
						}
					},
					"status": "OK",
					"code": 200,
					"_postman_previewlanguage": "json",
					"header": [
						{
							"key": "Content-Type",
							"value": "application/json"
						},
						{
							"key": "Content-Length",
							"value": "425"
						},
						{
							"key": "Connection",
							"value": "keep-alive"
						},
						{
							"key": "Date",
							"value": "Tue, 31 Aug 2021 15:30:03 GMT"
						},
						{
							"key": "x-amzn-RequestId",
							"value": "d5e6d8a6-7650-4844-a5e3-eb30dbe26184"
						},
						{
							"key": "Access-Control-Allow-Origin",
							"value": "*"
						},
						{
							"key": "x-amz-apigw-id",
							"value": "E8CmFEtnCYcFgLA="
						},
						{
							"key": "X-Amzn-Trace-Id",
							"value": "Root=1-612e4af3-73bbe87d07c463ca2b04057e"
						},
						{
							"key": "X-Cache",
							"value": "Miss from cloudfront"
						},
						{
							"key": "Via",
							"value": "1.1 819fb1f29c3038ca3cec04e041a0aa1f.cloudfront.net (CloudFront)"
						},
						{
							"key": "X-Amz-Cf-Pop",
							"value": "GRU1-C1"
						},
						{
							"key": "X-Amz-Cf-Id",
							"value": "YZ-IIL-CNS4tstQyBJOnbl_Tx82_qxJdYdErkv4lgDYVGutvPe2QEw=="
						}
					],
					"cookie": [],
					"body": "{\n    \"order_id\": \"123654789158252571\",\n    \"solicitation_date\": \"2022-03-31 12:30:02\",\n    \"due_date\": \"2022-04-04\",\n    \"code_identify\": 66543545,\n    \"amount\": \"250.90\",\n    \"bank_name\": \"BANK\",\n    \"holder\": \"HOLDER ACCOUNT BANK\",\n    \"agency\": \"0000\",\n    \"type_account\": \"corrente\",\n    \"account\": \"0000000\",\n    \"status\": \"pending\",\n    \"link_invoice\": \"https://administrator.fastpayments.com/boleto/token/3635121681813854684\",\n    \"bar_code\": \"99658430070605681659365889960383597650000002055\"\n}"
				}
			]
		},
		{
			"name": "GET DEPOSIT",
			"request": {
				"method": "GET",
				"header": [
					{
						"key": "Token",
						"value": "4a3df7c6c9168fd475b08e6ce2ac5f256873ed74",
						"type": "text"
					},
					{
						"key": "Accept",
						"value": "application/json",
						"type": "text"
					}
				],
				"url": {
					"raw": "'.$baseUrl.'/deposit",
					"host": [
						"'.$baseUrl.'"
					],
					"path": [
						"deposit"
					],
					"query": [
						{
							"key": "first_date",
							"value": "2022-04-01",
							"description": "Initial date of search",
							"disabled": true
						},
						{
							"key": "last_date",
							"value": "2022-04-30",
							"description": "Final date of search",
							"disabled": true
						},
						{
							"key": "status",
							"value": "confirmed",
							"description": "Transaction status separated by commas. Ex (confirmed,pending,canceled,refund,chargeback,freeze)",
							"disabled": true
						},
						{
							"key": "method",
							"value": "pix",
							"description": "Transaction method separated by commas. Ex (pix,invoice,shop,creditcard)",
							"disabled": true
						},
						{
							"key": "order_id",
							"value": null,
							"description": "Transaction Order ID",
							"disabled": true
						}
					]
				}
			},
			"response": [
				{
					"name": "GET DEPOSIT",
					"originalRequest": {
						"method": "GET",
						"header": [
							{
								"key": "Token",
								"value": "'.$token.'",
								"type": "text"
							}
						],
						"url": {
							"raw": "'.$baseUrl.'/deposit?first_date=2021-07-30&last_date=2021-08-01&status=confirmed&method=pix&order_id",
							"host": [
								"'.$baseUrl.'"
							],
							"path": [
								"deposit"
							],
							"query": [
								{
									"key": "first_date",
									"value": "2021-07-30",
									"description": "Initial date of search"
								},
								{
									"key": "last_date",
									"value": "2021-08-01",
									"description": "Final date of search"
								},
								{
									"key": "status",
									"value": "confirmed",
									"description": "Transaction status separated by commas. Ex (confirmed,pending,canceled,refund,chargeback,freeze)"
								},
								{
									"key": "method",
									"value": "pix",
									"description": "Transaction method separated by commas. Ex (pix,invoice,shop,creditcard)"
								},
								{
									"key": "order_id",
									"value": null,
									"description": "Transaction Order ID"
								}
							]
						}
					},
					"status": "OK",
					"code": 200,
					"_postman_previewlanguage": "json",
					"header": [
						{
							"key": "Content-Type",
							"value": "application/json"
						},
						{
							"key": "Content-Length",
							"value": "1916"
						},
						{
							"key": "Connection",
							"value": "keep-alive"
						},
						{
							"key": "Date",
							"value": "Tue, 31 Aug 2021 16:31:12 GMT"
						},
						{
							"key": "x-amzn-RequestId",
							"value": "e01c0a2f-3ba6-4324-a46c-520a62fbb728"
						},
						{
							"key": "Access-Control-Allow-Origin",
							"value": "*"
						},
						{
							"key": "x-amz-apigw-id",
							"value": "E8LkYFPkiYcFz7g="
						},
						{
							"key": "X-Amzn-Trace-Id",
							"value": "Root=1-612e594f-47a3f5c840640d5773c8a3fd"
						},
						{
							"key": "X-Cache",
							"value": "Miss from cloudfront"
						},
						{
							"key": "Via",
							"value": "1.1 b97800dba63a54d15f1e69f88e3a1a3e.cloudfront.net (CloudFront)"
						},
						{
							"key": "X-Amz-Cf-Pop",
							"value": "GRU1-C1"
						},
						{
							"key": "X-Amz-Cf-Id",
							"value": "zGlxCO82D2mRLpde5-bM29h-StH6YIRVli-ndtsZN648SdTNDb_jVQ=="
						}
					],
					"cookie": [],
					"body": "{\n    \"orders\": [\n        {\n            \"id\": 354812,\n            \"order_id\": \"9954535455\",\n            \"paid_date\": \"2022-03-31 13:50:06\",\n            \"due_date\": \"2022-04-04\",\n            \"code_identify\": \"FST01388887\",\n            \"amount_solicitation\": \"180.20\",\n            \"amount_confirmed\": \"180.20\",\n            \"code_bank\": \"1531545\",\n            \"bank_name\": \"Bank Name\",\n            \"holder\": \"Account Bank Name\",\n            \"agency\": \"Agency Bank\",\n            \"type_account\": \"corrente\",\n            \"account\": \"Account Number\",\n            \"status\": \"confirmed\",\n            \"link_invoice\": \"https://admin.fastpayments.com.br/qr/467492/2143517/200x200\",\n            \"receipt\": \"\",\n            \"comission\": \"0.9\",\n            \"disponibilization_date\": \"12/08/2021 00:00:00\"\n        },\n        {\n            \"id\": 467493,\n            \"order_id\": \"Order ID\",\n            \"paid_date_clear\": \"2021-08-14 13:50:06\",\n            \"paid_date\": \"14/08/2021 13:50:06\",\n            \"due_date\": \"15/08/2021\",\n            \"code_identify\": \"A4P66551491\",\n            \"amount_solicitation\": \"7.30\",\n            \"amount_confirmed\": \"7.30\",\n            \"code_bank\": \"Code Bank\",\n            \"bank_name\": \"Bank Name\",\n            \"holder\": \"Account Bank Name\",\n            \"agency\": \"Agency Bank\",\n            \"type_account\": \"corrente\",\n            \"account\": \"Account Number\",\n            \"status\": \"confirmed\",\n            \"link_invoice\": \"https://admin.fastpayments.com.br/qr/467492/2753517/200x200\",\n            \"receipt\": \"\",\n            \"comission\": \"0.9\",\n            \"disponibilization_date\": \"15/08/2021 00:00:00\"\n        }\n    ]\n}"
				}
			]
		},
		{
			"name": "CANCEL DEPOSIT",
			"request": {
				"method": "DELETE",
				"header": [
					{
						"key": "Token",
						"value": "'.$token.'",
						"type": "text"
					}
				],
				"body": {
					"mode": "raw",
					"raw": "{\r\n    \"order_id\" : \"Your order id\"\r\n}",
					"options": {
						"raw": {
							"language": "json"
						}
					}
				},
				"url": {
					"raw": "'.$baseUrl.'/deposit",
					"host": [
						"'.$baseUrl.'"
					],
					"path": [
						"deposit"
					]
				}
			},
			"response": []
		},
		{
			"name": "CREATE WITHDRAW",
			"request": {
				"method": "POST",
				"header": [
					{
						"key": "Token",
						"value": "'.$token.'",
						"type": "text"
					}
				],
				"body": {
					"mode": "raw",
					"raw": "{\r\n    \"method\" : \"pix\",\r\n    \"order_id\" : \"123456789\",\r\n    \"user_id\" : \"123456\",\r\n    \"user_name\": \"User name\",\r\n    \"user_document\": \"User document\",\r\n    \"amount\" : \"250.00\"\r\n}",
					"options": {
						"raw": {
							"language": "json"
						}
					}
				},
				"url": {
					"raw": "'.$baseUrl.'/withdraw",
					"host": [
						"'.$baseUrl.'"
					],
					"path": [
						"withdraw"
					]
				}
			},
			"response": [
				{
					"name": "CREATE WITHDRAW",
					"originalRequest": {
						"method": "POST",
						"header": [
							{
								"key": "Token",
								"value": "'.$token.'",
								"type": "text"
							}
						],
						"body": {
							"mode": "raw",
							"raw": "{\r\n    \"method\" : \"pix\",\r\n    \"order_id\" : \"12345678TTT9\",\r\n    \"user_id\" : \"User ID\",\r\n    \"user_name\": \"User name\",\r\n    \"user_document\": \"User document\",\r\n    \"amount\" : \"1.54\"\r\n}",
							"options": {
								"raw": {
									"language": "json"
								}
							}
						},
						"url": {
							"raw": "'.$baseUrl.'/withdraw",
							"host": [
								"'.$baseUrl.'"
							],
							"path": [
								"withdraw"
							]
						}
					},
					"status": "OK",
					"code": 200,
					"_postman_previewlanguage": "json",
					"header": [
						{
							"key": "Content-Type",
							"value": "application/json"
						},
						{
							"key": "Content-Length",
							"value": "300"
						},
						{
							"key": "Connection",
							"value": "keep-alive"
						},
						{
							"key": "Date",
							"value": "Tue, 31 Aug 2021 17:42:30 GMT"
						},
						{
							"key": "x-amzn-RequestId",
							"value": "770c9b07-e59f-4686-8535-08967948aa34"
						},
						{
							"key": "Access-Control-Allow-Origin",
							"value": "*"
						},
						{
							"key": "x-amz-apigw-id",
							"value": "E8WAWFDWiYcFxwA="
						},
						{
							"key": "X-Amzn-Trace-Id",
							"value": "Root=1-612e6a02-05e1011b2586a8bd30b1e74b"
						},
						{
							"key": "X-Cache",
							"value": "Miss from cloudfront"
						},
						{
							"key": "Via",
							"value": "1.1 5737857b517c9071e8cc21326fd104a6.cloudfront.net (CloudFront)"
						},
						{
							"key": "X-Amz-Cf-Pop",
							"value": "GRU3-C2"
						},
						{
							"key": "X-Amz-Cf-Id",
							"value": "kmfd5oeamHPnmsCXKOWDKybx7eJqSXic1dbS9WVAbd3newPLORZMXA=="
						}
					],
					"cookie": [],
					"body": "{\n    \"id\": 498566,\n    \"order_id\": \"12345678TTT9\",\n    \"solicitation_date\": \"2021-08-31 14:42:30\",\n    \"user_id\": \"User ID\",\n    \"user_name\": \"User name\",\n    \"user_document\": \"User document\",\n    \"bank_name\": null,\n    \"agency\": null,\n    \"type_operation\": null,\n    \"account\": null,\n    \"amount_solicitation\": \"1.54\",\n    \"currency\": null,\n    \"status\": \"pending\"\n}"
				}
			]
		},
		{
			"name": "GET WITHDRAW",
			"request": {
				"method": "GET",
				"header": [
					{
						"key": "Token",
						"value": "'.$token.'",
						"type": "text"
					}
				],
				"url": {
					"raw": "'.$baseUrl.'/withdraw?order_id=TESTE15153561",
					"host": [
						"'.$baseUrl.'"
					],
					"path": [
						"withdraw"
					],
					"query": [
						{
							"key": "order_id",
							"value": "TESTE15153561"
						}
					]
				}
			},
			"response": [
				{
					"name": "GET WITHDRAW",
					"originalRequest": {
						"method": "GET",
						"header": [
							{
								"key": "Token",
								"value": "'.$token.'",
								"type": "text"
							}
						],
						"url": {
							"raw": "'.$baseUrl.'/withdraw?order_id=TESTE15153561",
							"host": [
								"'.$baseUrl.'"
							],
							"path": [
								"withdraw"
							],
							"query": [
								{
									"key": "order_id",
									"value": "TESTE15153561"
								}
							]
						}
					},
					"status": "OK",
					"code": 200,
					"_postman_previewlanguage": "json",
					"header": [
						{
							"key": "Content-Type",
							"value": "application/json"
						},
						{
							"key": "Content-Length",
							"value": "330"
						},
						{
							"key": "Connection",
							"value": "keep-alive"
						},
						{
							"key": "Date",
							"value": "Tue, 31 Aug 2021 17:41:24 GMT"
						},
						{
							"key": "x-amzn-RequestId",
							"value": "9523858b-befa-44fa-ab7b-58be0826c7a9"
						},
						{
							"key": "Access-Control-Allow-Origin",
							"value": "*"
						},
						{
							"key": "x-amz-apigw-id",
							"value": "E8V2fHTsiYcFUYg="
						},
						{
							"key": "X-Amzn-Trace-Id",
							"value": "Root=1-612e69c2-184f412d6aec985114ca837b"
						},
						{
							"key": "X-Cache",
							"value": "Miss from cloudfront"
						},
						{
							"key": "Via",
							"value": "1.1 5737857b517c9071e8cc21326fd104a6.cloudfront.net (CloudFront)"
						},
						{
							"key": "X-Amz-Cf-Pop",
							"value": "GRU3-C2"
						},
						{
							"key": "X-Amz-Cf-Id",
							"value": "IXjOEzskmMuBNOAgfRbexpYn1X71QAJgMNlUiAMA2E70lHJERrWzYw=="
						}
					],
					"cookie": [],
					"body": "{\n    \"order_id\": \"TESTET15153561\",\n    \"solicitation_date_clear\": \"2021-08-30 16:02:48\",\n    \"solicitation_date\": \"30/08/2021 16:02:48\",\n    \"code_identify\": null,\n    \"amount_solicitation\": \"1.66\",\n    \"code_bank\": \"218\",\n    \"bank_name\": \"---\",\n    \"holder\": \"User name\",\n    \"agency\": \"---\",\n    \"type_account\": \"---\",\n    \"account\": \"---\",\n    \"document\": \"User document\",\n    \"status\": \"canceled\"\n}"
				}
			]
		}
	]
}';

header('Content-disposition: attachment; filename='.strtolower($trading_name).'-postman-collection.json');
header('Content-type: application/json');

echo($var_json);
exit();
?>
