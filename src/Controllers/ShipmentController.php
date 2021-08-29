<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 01-Nov-17
 * Time: 6:05 PM
 */

namespace Controllers;

use Constants\Messages;
use Constants\NotifyMessages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;
use Utils\PushNotifications as Notification;

class ShipmentController extends ControllerBase
{
    protected $container;
    protected $db;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $this->container->primary;
    }

	public function getShipmentUsersList($request, $response)
	{
		
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $userSql = "select USER_ID, SUPER_ADMIN, IS_SHIPPER FROM ptf_user_details WHERE USER_ID = :userId";
                            $userStatement = $this->db->prepare($userSql);
                            $userStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $userStatement->execute();
                            $user = $userStatement->fetch(\PDO::FETCH_ASSOC);
                            if ($user) {
                                if($user['SUPER_ADMIN'] == 1) {
                                    $shipmentSql = "SELECT COUNT(cor_shipments.SHIPMENT_TITLE) AS total, cor_shipments.SHIPMENT_ID, cor_shipments.CREATOR_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE  cor_shipments.SHIPMENT_STATUS = 'SHIPPED' GROUP BY cor_shipments.CREATOR_ID ORDER BY cor_shipments.SHIPMENT_CATEGORY DESC, cor_shipments.CUSTOMER_NAME DESC ";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                }  else {
                                    $shipmentSql = "SELECT COUNT(cor_shipments.SHIPMENT_TITLE) AS total, cor_shipments.SHIPMENT_ID, cor_shipments.CREATOR_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME   FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE  cor_shipments.CREATOR_ID = :userId AND cor_shipments.SHIPMENT_STATUS = 'SHIPPED' GROUP BY cor_shipments.CREATOR_ID  ORDER BY cor_shipments.SHIPMENT_CATEGORY DESC, cor_shipments.CUSTOMER_NAME DESC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
									$shipmentStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
 
                                }
                                $shipmentStatement->execute();
                                $shipments = $shipmentStatement->fetchAll(\PDO::FETCH_ASSOC);

                                if(!empty($shipments)) {
                                    foreach ($shipments as $key => $shipment) {
                                        $shipmentImageSql = "SELECT * FROM cor_shipments_images WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentImageStatement = $this->db->prepare($shipmentImageSql);
                                        $shipmentImageStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                        $shipmentImageStatement->execute();
                                        $shipmentImages = $shipmentImageStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if (!empty($shipmentImages)) {
                                            foreach ($shipmentImages as $key1 => $image) {
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE_ID'] = $image['SHIPMENT_IMAGE_ID'];
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE'] = '/storage/shipments/' . $image['SHIPMENT_IMAGE'];
                                            }
                                        } else {
                                            $shipments[$key]['images'] = array();
                                        }

                                        $shipmentStatusSql = "SELECT * FROM cor_shipments_log WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentStatusStatement = $this->db->prepare($shipmentStatusSql);
                                        $shipmentStatusStatement->execute();
                                        $shipmentStatuses = $shipmentStatusStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if(!empty($shipmentStatuses)) {
                                            $shipments[$key]['logs'] = $shipmentStatuses;
                                        } else {
                                            $shipments[$key]['logs'] = array();
                                        }
                                    }

                                    return $this->sendHttpResponseSuccess($response, $shipments);

                                } else {
                                    return $this->sendHttpResponseSuccess($response, $shipments);
                                }
                            } else {
                                return $this->sendHttpResponseSuccess($response, 'User account does not exist');
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    
	}
	
	public function getCustomersList($request, $response)
	{		
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $customerSql = "SELECT CUSTOMER_NAME, COUNT(SHIPMENT_ID) AS frequency FROM `cor_shipments` GROUP BY CUSTOMER_NAME ORDER BY CUSTOMER_NAME";
                            $customerStatement = $this->db->prepare($customerSql);                            
                            $customerStatement->execute();
                            $customers = $customerStatement->fetchAll(\PDO::FETCH_ASSOC);
                            if ($customers) {
								return $this->sendHttpResponseSuccess($response, $customers);
								
							} else {
                                return $this->sendHttpResponseSuccess($response, 'Customers does not exist');
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    
	}
 
	
    public function createShipment($request, $response, $arg) {
        try {
				$params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::SHIPMENT_TITLE] == "" || $params[ParamKeys::CREATOR_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $userSql = "select SUPER_ADMIN FROM ptf_user_details WHERE USER_ID = :userId";
                            $userStatement = $this->db->prepare($userSql);
                            $userStatement->bindParam(":userId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                            $userStatement->execute();
                            $user = $userStatement->fetch(\PDO::FETCH_ASSOC);

                            $this->db->beginTransaction();
                            if($user['SUPER_ADMIN'] == 1) {
                                $statementShipment = $this->db->prepare('INSERT INTO cor_shipments (SHIPMENT_TITLE, SHIPMENT_DESCRIPTION, 
                                                                SHIPMENT_CATEGORY, CUSTOMER_NAME, INVOICE_NUMBER, CREATOR_ID, CREATED_DATE, SHIPMENT_STATUS) 
                                                                VALUES(:shipmentTitle, :shipmentDescription, :shipmentCategory, 
                                                                :customerName, :invoiceNumber, :creatorId, :createdDate, "APPROVED")');
                            } else {
                                $statementShipment = $this->db->prepare('INSERT INTO cor_shipments (SHIPMENT_TITLE, SHIPMENT_DESCRIPTION, 
                                                                SHIPMENT_CATEGORY, CUSTOMER_NAME, INVOICE_NUMBER, CREATOR_ID, CREATED_DATE) 
                                                                VALUES(:shipmentTitle, :shipmentDescription, :shipmentCategory, 
                                                                :customerName, :invoiceNumber, :creatorId, :createdDate)');
                            }

                            $statementShipment->bindParam(":shipmentTitle", $params[ParamKeys::SHIPMENT_TITLE], \PDO::PARAM_STR);
                            $statementShipment->bindParam(":shipmentDescription", $params[ParamKeys::SHIPMENT_DESCRIPTION], \PDO::PARAM_STR);
                            $statementShipment->bindParam(":shipmentCategory", $params[ParamKeys::SHIPMENT_CATEGORY], \PDO::PARAM_STR);
                            $statementShipment->bindParam(":customerName", $params[ParamKeys::CUSTOMER_NAME], \PDO::PARAM_STR);
                            $statementShipment->bindParam(":invoiceNumber", $params[ParamKeys::INVOICE_NUMBER], \PDO::PARAM_STR);
                            $statementShipment->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                            $statementShipment->bindParam(":createdDate", $params[ParamKeys::CREATED_DATE], \PDO::PARAM_STR);
                            $statementShipment->execute();
                            $shipmentId = $this->db->lastInsertId();
                            $this->db->commit();
                            if ($shipmentId > 0) {
                                $uploadedFiles = $request->getUploadedFiles();
                                if (!empty($uploadedFiles)) {
                                    foreach ($uploadedFiles['SHIPMENT_IMAGES'] as $uploadedFile) {
                                        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                            $filename = $this->moveUploadedFile('storage/shipments', $uploadedFile);

                                            $this->addShipmentImages($filename, $shipmentId);
                                        }
                                    }
                                }

                                $shipmentStatement = $this->db->prepare('select * from cor_shipments where SHIPMENT_ID = :shipmentId');
                                $shipmentStatement->bindParam(":shipmentId", $shipmentId, \PDO::PARAM_STR);
                                $shipmentStatement->execute();
                                $data = $shipmentStatement->fetch(\PDO::FETCH_ASSOC);

                                $this->notifySuperAdmins($shipmentId,$params, 'NEW_SHIPMENT_ADDED');
								
								// add notification to table /////
						/*		
							
					$notification_type = "NEW_SHIPMENT_ADDED";								
					$notification = "NEW SHIPMENT/RMA CREATED FOR CUSTOMER [".$data['CUSTOMER_NAME']."], CATEGORY [".$data['SHIPMENT_CATEGORY']."], AMOUNT [".$data['SHIPMENT_TITLE']."]";
					$obj_type = 'shipment';			

					if ( $this->db ) {
						$this->db->beginTransaction();
						$statementTask = $this->db->prepare( 'INSERT INTO  cor_notifications (NOTIFICATION, NOTIFICATION_TYPE, SENDER_ID, RECEIVER_ID, OBJECT_ID, OBJECT_TYPE) 
                                                                    VALUES(:notification, :notification_type, :senderId, :receiverId, :objectId, :objectType)' );



						$statementTask->bindParam( ":notification", $notification );
						$statementTask->bindParam( ":notification_type", $notification_type );
						$statementTask->bindParam( ":senderId", $params[ ParamKeys::CREATOR_ID ], \PDO::PARAM_STR );
						$statementTask->bindParam( ":receiverId", $params[ ParamKeys::CREATOR_ID ], \PDO::PARAM_STR );
						$statementTask->bindParam( ":objectId", $shipmentId, \PDO::PARAM_STR );
						$statementTask->bindParam( ":objectType", $obj_type, \PDO::PARAM_STR );
						$statementTask->execute();
						$lastInsertId = $this->db->lastInsertId();
						if ( $this->db->commit() ) {
							// send notification							
							//return $this->sendHttpResponseMessage( $response, "notification: " . $notification );
							// SUCCESS
						}
					} else {
						return $this->sendHTTPResponseError( $response, Messages::MSG_ERR_DB_CONNECTION );
					}
				
							*/	
								
								// end add notfication to table ///

                                return $this->sendHttpResponseSuccess($response, $data);

                            } else {
                                return $this->sendHTTPResponseError($response, 'Unable to create shipment!');
                            }
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            //$this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function updateShipment($request, $response) {
        try {
            $params = $request->getParsedBody();
            $shipmentStatus = 'PENDING';

            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::SHIPMENT_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $this->db->beginTransaction();
                            $shipmentStatement = $this->db->prepare('UPDATE cor_shipments SET SHIPMENT_TITLE = :shipmentTitle, 
                                                                  SHIPMENT_DESCRIPTION = :shipmentDescription, SHIPMENT_STATUS = :status,
                                                                  UPDATED_DATE = :updateDate
                                                                  WHERE SHIPMENT_ID = :shipmentId');
                            $shipmentStatement->bindParam(":shipmentTitle", $params[ParamKeys::SHIPMENT_TITLE], \PDO::PARAM_STR);
                            $shipmentStatement->bindParam(":shipmentDescription", $params[ParamKeys::SHIPMENT_DESCRIPTION], \PDO::PARAM_STR);
                            $shipmentStatement->bindParam(":status", $shipmentStatus, \PDO::PARAM_STR);
                            $shipmentStatement->bindParam(":updateDate", $params[ParamKeys::UPDATED_DATE], \PDO::PARAM_STR);
                            $shipmentStatement->bindParam(":shipmentId", $params[ParamKeys::SHIPMENT_ID], \PDO::PARAM_STR);
                            $shipmentStatement->execute();
                            if ($this->db->commit()) {

                                $shipmentid = $params[ParamKeys::SHIPMENT_ID];

                                $uploadedFiles = $request->getUploadedFiles();
                                if (!empty($uploadedFiles)) {

                                    $this->deleteShipmentImages($shipmentid);

                                    foreach ($uploadedFiles['SHIPMENT_IMAGES'] as $uploadedFile) {
                                        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                            $filename = $this->moveUploadedFile('storage/shipments', $uploadedFile);

                                            $this->addShipmentImages($filename, $shipmentid);
                                        }
                                    }
                                }

                                $this->notifySuperAdmins($params[ParamKeys::SHIPMENT_ID], $params, 'SHIPMENT_UPDATED');

                                return $this->sendHttpResponseMessage($response, "Shipment Updated successfully");
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
//            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function fetchShipments($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $userSql = "select USER_ID, SUPER_ADMIN, IS_SHIPPER FROM ptf_user_details WHERE USER_ID = :userId";
                            $userStatement = $this->db->prepare($userSql);
                            $userStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $userStatement->execute();
                            $user = $userStatement->fetch(\PDO::FETCH_ASSOC);
                            if ($user) {
                                if($user['SUPER_ADMIN'] == 1) {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                } else if ($user['IS_SHIPPER'] == 1) {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE SHIPMENT_STATUS = 'APPROVED' AND (SHIPMENT_NUMBER = '' OR SHIPMENT_NUMBER IS NULL) ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                } else {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE CREATOR_ID = :userId ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    $shipmentStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                }
								
								
                                $shipmentStatement->execute();
                                $shipments = $shipmentStatement->fetchAll(\PDO::FETCH_ASSOC);

                                if(!empty($shipments)) {
                                    foreach ($shipments as $key => $shipment) {
                                        $shipmentImageSql = "SELECT * FROM cor_shipments_images WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentImageStatement = $this->db->prepare($shipmentImageSql);
                                        $shipmentImageStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                        $shipmentImageStatement->execute();
                                        $shipmentImages = $shipmentImageStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if (!empty($shipmentImages)) {
                                            foreach ($shipmentImages as $key1 => $image) {
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE_ID'] = $image['SHIPMENT_IMAGE_ID'];
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE'] = '/storage/shipments/' . $image['SHIPMENT_IMAGE'];
                                            }
                                        } else {
                                            $shipments[$key]['images'] = array();
                                        }

                                        $shipmentStatusSql = "SELECT * FROM cor_shipments_log WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentStatusStatement = $this->db->prepare($shipmentStatusSql);
                                        $shipmentStatusStatement->execute();
                                        $shipmentStatuses = $shipmentStatusStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if(!empty($shipmentStatuses)) {
                                            $shipments[$key]['logs'] = $shipmentStatuses;
                                        } else {
                                            $shipments[$key]['logs'] = array();
                                        }
                                    }

                                    return $this->sendHttpResponseSuccess($response, $shipments);

                                } else {
                                    return $this->sendHttpResponseSuccess($response, $shipments);
                                }
                            } else {
                                return $this->sendHttpResponseSuccess($response, 'User account does not exist');
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

   public function getShipmentUsersGroup($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::CREATOR_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $userSql = "select USER_ID, SUPER_ADMIN, IS_SHIPPER FROM ptf_user_details WHERE USER_ID = :userId";
                            $userStatement = $this->db->prepare($userSql);
                            $userStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $userStatement->execute();
                            $user = $userStatement->fetch(\PDO::FETCH_ASSOC);
                            if ($user) {
                                if($user['SUPER_ADMIN'] == 1) {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_STATUS = 'SHIPPED' AND cor_shipments.CREATOR_ID = :creatorId ORDER BY DAYS DESC";
									 $shipmentStatement = $this->db->prepare($shipmentSql);
                                    $shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);									
                                }elseif($user['SUPER_ADMIN'] == 0 && $user['IS_SHIPPER'] == 0) {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_STATUS = 'SHIPPED' AND cor_shipments.CREATOR_ID = :creatorId ORDER BY DAYS DESC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    $shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                } else {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_CATEGORY = 'RAFIQ' AND cor_shipments.SHIPMENT_STATUS = 'SHIPPED' AND cor_shipments.CREATOR_ID = :creatorId ORDER BY DAYS DESC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    $shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                }
                                $shipmentStatement->execute();
                                $shipments = $shipmentStatement->fetchAll(\PDO::FETCH_ASSOC);

                                if(!empty($shipments)) {
                                    foreach ($shipments as $key => $shipment) {
                                        $shipmentImageSql = "SELECT * FROM cor_shipments_images WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentImageStatement = $this->db->prepare($shipmentImageSql);
                                        $shipmentImageStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                        $shipmentImageStatement->execute();
                                        $shipmentImages = $shipmentImageStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if (!empty($shipmentImages)) {
                                            foreach ($shipmentImages as $key1 => $image) {
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE_ID'] = $image['SHIPMENT_IMAGE_ID'];
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE'] = '/storage/shipments/' . $image['SHIPMENT_IMAGE'];
                                            }
                                        } else {
                                            $shipments[$key]['images'] = array();
                                        }

                                        $shipmentStatusSql = "SELECT * FROM cor_shipments_log WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentStatusStatement = $this->db->prepare($shipmentStatusSql);
                                        $shipmentStatusStatement->execute();
                                        $shipmentStatuses = $shipmentStatusStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if(!empty($shipmentStatuses)) {
                                            $shipments[$key]['logs'] = $shipmentStatuses;
                                        } else {
                                            $shipments[$key]['logs'] = array();
                                        }
                                    }

                                    return $this->sendHttpResponseSuccess($response, $shipments);

                                } else {
                                    return $this->sendHttpResponseSuccess($response, $shipments);
                                }
                            } else {
                                return $this->sendHttpResponseSuccess($response, 'User account does not exist');
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

	public function getShipmentUsersGroupCustomers($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::CREATOR_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $userSql = "select USER_ID, SUPER_ADMIN, IS_SHIPPER FROM ptf_user_details WHERE USER_ID = :userId";
                            $userStatement = $this->db->prepare($userSql);
                            $userStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $userStatement->execute();
                            $user = $userStatement->fetch(\PDO::FETCH_ASSOC);
                            if ($user) {
                                if($user['SUPER_ADMIN'] == 1) {
                                    $shipmentSql = "SELECT CUSTOMER_NAME, COUNT(SHIPMENT_ID) AS total,SUM(SHIPMENT_TITLE) AS ptotal, COUNT(CASE WHEN FINAL_STATUS = 'PENDING' THEN 1 END) AS pending, SUM(CASE WHEN FINAL_STATUS = 'PENDING' THEN SHIPMENT_TITLE END) AS ppending, COUNT(CASE WHEN FINAL_STATUS = 'RECEIVED' THEN 1 END) AS received, SUM(CASE WHEN FINAL_STATUS = 'RECEIVED' THEN SHIPMENT_TITLE END) AS preceived FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_STATUS = 'SHIPPED' AND cor_shipments.CREATOR_ID = :creatorId GROUP BY CUSTOMER_NAME";
									 $shipmentStatement = $this->db->prepare($shipmentSql);
                                    $shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);									
                                }elseif($user['SUPER_ADMIN'] == 0 && $user['IS_SHIPPER'] == 0) {
                                    $shipmentSql = "SELECT CUSTOMER_NAME, COUNT(SHIPMENT_ID) AS total,SUM(SHIPMENT_TITLE) AS ptotal, COUNT(CASE WHEN FINAL_STATUS = 'PENDING' THEN 1 END) AS pending, SUM(CASE WHEN FINAL_STATUS = 'PENDING' THEN SHIPMENT_TITLE END) AS ppending, COUNT(CASE WHEN FINAL_STATUS = 'RECEIVED' THEN 1 END) AS received, SUM(CASE WHEN FINAL_STATUS = 'RECEIVED' THEN SHIPMENT_TITLE END) AS preceived FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_STATUS = 'SHIPPED' AND cor_shipments.CREATOR_ID = :creatorId GROUP BY CUSTOMER_NAME";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    $shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                } else {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_CATEGORY = 'RAFIQ' AND cor_shipments.SHIPMENT_STATUS = 'SHIPPED' AND cor_shipments.CREATOR_ID = :creatorId ORDER BY DAYS DESC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    $shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                }
                                $shipmentStatement->execute();
                                $shipments = $shipmentStatement->fetchAll(\PDO::FETCH_ASSOC);
								return $this->sendHttpResponseSuccess($response, $shipments);																	
                            } else {
                                return $this->sendHttpResponseSuccess($response, 'User account does not exist');
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	
	 public function getShipmentUsersGroupSearch($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "" ) {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $userSql = "select USER_ID, SUPER_ADMIN, IS_SHIPPER FROM ptf_user_details WHERE USER_ID = :userId";
                            $userStatement = $this->db->prepare($userSql);
                            $userStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $userStatement->execute();
                            $user = $userStatement->fetch(\PDO::FETCH_ASSOC);
                            if ($user) 
							{
								if($params[ParamKeys::TASK_TITLE] != "")
								{
									
                                if($user['SUPER_ADMIN'] == 1) {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE ( cor_shipments.SHIPMENT_DESCRIPTION LIKE '%".$params[ParamKeys::TASK_TITLE]."%' OR  cor_shipments.CUSTOMER_NAME LIKE '%".$params[ParamKeys::TASK_TITLE]."%' ) AND cor_shipments.SHIPMENT_STATUS = 'SHIPPED' ORDER BY DAYS DESC";
									 $shipmentStatement = $this->db->prepare($shipmentSql);
                                   // $shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);									
                                }elseif($user['SUPER_ADMIN'] == 0 && $user['IS_SHIPPER'] == 0) {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE ( cor_shipments.SHIPMENT_DESCRIPTION LIKE '%".$params[ParamKeys::TASK_TITLE]."%' OR  cor_shipments.CUSTOMER_NAME LIKE '%".$params[ParamKeys::TASK_TITLE]."%' ) AND cor_shipments.SHIPMENT_STATUS = 'SHIPPED' ORDER BY DAYS DESC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    //$shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                } else {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE ( cor_shipments.SHIPMENT_DESCRIPTION LIKE '%".$params[ParamKeys::TASK_TITLE]."%' OR  cor_shipments.CUSTOMER_NAME LIKE '%".$params[ParamKeys::TASK_TITLE]."%' ) AND cor_shipments.SHIPMENT_CATEGORY = 'RAFIQ' AND cor_shipments.SHIPMENT_STATUS = 'SHIPPED' ORDER BY DAYS DESC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    //$shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                }
                                $shipmentStatement->execute();
                                $shipments = $shipmentStatement->fetchAll(\PDO::FETCH_ASSOC);

                                if(!empty($shipments)) {
                                    foreach ($shipments as $key => $shipment) {
                                        $shipmentImageSql = "SELECT * FROM cor_shipments_images WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentImageStatement = $this->db->prepare($shipmentImageSql);
                                        $shipmentImageStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                        $shipmentImageStatement->execute();
                                        $shipmentImages = $shipmentImageStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if (!empty($shipmentImages)) {
                                            foreach ($shipmentImages as $key1 => $image) {
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE_ID'] = $image['SHIPMENT_IMAGE_ID'];
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE'] = '/storage/shipments/' . $image['SHIPMENT_IMAGE'];
                                            }
                                        } else {
                                            $shipments[$key]['images'] = array();
                                        }

                                        $shipmentStatusSql = "SELECT * FROM cor_shipments_log WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentStatusStatement = $this->db->prepare($shipmentStatusSql);
                                        $shipmentStatusStatement->execute();
                                        $shipmentStatuses = $shipmentStatusStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if(!empty($shipmentStatuses)) {
                                            $shipments[$key]['logs'] = $shipmentStatuses;
                                        } else {
                                            $shipments[$key]['logs'] = array();
                                        }
                                    }

                                    return $this->sendHttpResponseSuccess($response, $shipments);

                                } else {
                                    return $this->sendHttpResponseSuccess($response, $shipments);
                                }
                            
								}
								else
								{
									
                                if($user['SUPER_ADMIN'] == 1) {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_STATUS = 'SHIPPED' ORDER BY DAYS DESC";
									 $shipmentStatement = $this->db->prepare($shipmentSql);
                                   // $shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);									
                                }elseif($user['SUPER_ADMIN'] == 0 && $user['IS_SHIPPER'] == 0) {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_STATUS = 'SHIPPED' ORDER BY DAYS DESC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    //$shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                } else {
                                    $shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME, timestampdiff(DAY,FROM_UNIXTIME(cor_shipments.CREATED_DATE),DATE_ADD(NOW(), INTERVAL 1 HOUR)) as DAYS  FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  WHERE cor_shipments.SHIPMENT_CATEGORY = 'RAFIQ' AND cor_shipments.SHIPMENT_STATUS = 'SHIPPED' ORDER BY DAYS DESC";
                                    $shipmentStatement = $this->db->prepare($shipmentSql);
                                    //$shipmentStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                }
                                $shipmentStatement->execute();
                                $shipments = $shipmentStatement->fetchAll(\PDO::FETCH_ASSOC);

                                if(!empty($shipments)) {
                                    foreach ($shipments as $key => $shipment) {
                                        $shipmentImageSql = "SELECT * FROM cor_shipments_images WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentImageStatement = $this->db->prepare($shipmentImageSql);
                                        $shipmentImageStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                        $shipmentImageStatement->execute();
                                        $shipmentImages = $shipmentImageStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if (!empty($shipmentImages)) {
                                            foreach ($shipmentImages as $key1 => $image) {
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE_ID'] = $image['SHIPMENT_IMAGE_ID'];
                                                $shipments[$key]['images'][$key1]['SHIPMENT_IMAGE'] = '/storage/shipments/' . $image['SHIPMENT_IMAGE'];
                                            }
                                        } else {
                                            $shipments[$key]['images'] = array();
                                        }

                                        $shipmentStatusSql = "SELECT * FROM cor_shipments_log WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
                                        $shipmentStatusStatement = $this->db->prepare($shipmentStatusSql);
                                        $shipmentStatusStatement->execute();
                                        $shipmentStatuses = $shipmentStatusStatement->fetchAll(\PDO::FETCH_ASSOC);

                                        if(!empty($shipmentStatuses)) {
                                            $shipments[$key]['logs'] = $shipmentStatuses;
                                        } else {
                                            $shipments[$key]['logs'] = array();
                                        }
                                    }

                                    return $this->sendHttpResponseSuccess($response, $shipments);

                                } else {
                                    return $this->sendHttpResponseSuccess($response, $shipments);
                                }
                            
								}
							
							} else {
                                return $this->sendHttpResponseSuccess($response, 'User account does not exist');
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	
    public function fetchDetails($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::OBJECT_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $shipmentSql = "SELECT cor_shipments.*,MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE SHIPMENT_ID =:objectId";
                            $shipmentStatement = $this->db->prepare($shipmentSql);
                            $shipmentStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                            $shipmentStatement->execute();
                            $details = $shipmentStatement->fetch(\PDO::FETCH_ASSOC);
                            if ($details) {
                                $shipmentImageSql = "SELECT * FROM cor_shipments_images WHERE SHIPMENT_ID = :objectId";
                                $shipmentImageStatement = $this->db->prepare($shipmentImageSql);
                                $shipmentImageStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                $shipmentImageStatement->execute();
                                $shipmentImages = $shipmentImageStatement->fetchAll(\PDO::FETCH_ASSOC);
                                if(!empty($shipmentImages)) {
                                    foreach ($shipmentImages as $key => $image) {
                                        $details['images'][$key]['SHIPMENT_IMAGE_ID'] = $image['SHIPMENT_IMAGE_ID'];
                                        $details['images'][$key]['SHIPMENT_IMAGE'] = '/storage/shipments/' . $image['SHIPMENT_IMAGE'];
                                    }
                                }
                                return $this->sendHttpResponseSuccess($response, $details);
                            } else {
                                return $this->sendHttpResponseSuccess($response, 'Unable to fetch shipment');
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function updateShipmentStatus($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::OBJECT_ID] == "" || $params[ParamKeys::SHIPMENT_STATUS] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $shipmentStatement = $this->db->prepare('SELECT * FROM cor_shipments WHERE SHIPMENT_ID = :objectId');
                            $shipmentStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                            $shipmentStatement->execute();
                            $details = $shipmentStatement->fetch(\PDO::FETCH_ASSOC);
                            if ($details) {
                                $this->db->beginTransaction();
                                if (!empty($params[ParamKeys::SHIPMENT_NUMBER])) {
                                    $shipmentStatement = $this->db->prepare('UPDATE cor_shipments SET SHIPMENT_STATUS = :status,
                                                                             UPDATED_DATE = :updateDate, SHIPMENT_NUMBER = :shipmentNumber
                                                                             WHERE SHIPMENT_ID = :objectId');
                                    $shipmentStatement->bindParam(":status",  $params[ParamKeys::SHIPMENT_STATUS], \PDO::PARAM_STR);
                                    $shipmentStatement->bindParam(":updateDate", $params[ParamKeys::UPDATED_DATE], \PDO::PARAM_STR);
                                    $shipmentStatement->bindParam(":shipmentNumber", $params[ParamKeys::SHIPMENT_NUMBER], \PDO::PARAM_STR);
                                    $shipmentStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                } else {
                                    $shipmentStatement = $this->db->prepare('UPDATE cor_shipments SET SHIPMENT_STATUS = :status,
                                                                             UPDATED_DATE = :updateDate
                                                                             WHERE SHIPMENT_ID = :objectId');
                                    $shipmentStatement->bindParam(":status",  $params[ParamKeys::SHIPMENT_STATUS], \PDO::PARAM_STR);
                                    $shipmentStatement->bindParam(":updateDate", $params[ParamKeys::UPDATED_DATE], \PDO::PARAM_STR);
                                    $shipmentStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                }

                                $shipmentStatement->execute();
                                if ($this->db->commit()) {
                                    if($params[ParamKeys::SHIPMENT_STATUS] == 'REJECTED') {
                                        $status = 'REJECTED';
                                        $this->db->beginTransaction();
                                        $shipmentStatement = $this->db->prepare('INSERT INTO cor_shipments_log SET SHIPMENT_ID = :objectId,
                                                                         USER_ID = :userId, STATUS = :status,
                                                                         CREATED_DATE = :createdDate');
                                        $shipmentStatement->bindParam(":objectId",  $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                        $shipmentStatement->bindParam(":userId",  $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                        $shipmentStatement->bindParam(":status", $status, \PDO::PARAM_STR);
                                        $shipmentStatement->bindParam(":createdDate", $params[ParamKeys::UPDATED_DATE], \PDO::PARAM_STR);
                                        $shipmentStatement->execute();
                                        $this->db->commit();

                                    } else if ($params[ParamKeys::SHIPMENT_STATUS] == 'APPROVED') {
                                        $this->notifyShippers($params[ParamKeys::OBJECT_ID], $params,'SHIPMENT_APPROVED');
                                    }

                                    $params[ParamKeys::USER_ID] = $details['CREATOR_ID'];

                                    $this->notifyShipmentCreator($params[ParamKeys::OBJECT_ID], $params, 'SHIPMENT_STATUS_CHANGED');

                                    return $this->sendHttpResponseMessage($response, "Shipment status updated");
                                }
                            } else {
                                return $this->sendHTTPResponseError($response, 'Unable to fetch shipment');
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
//          $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function updateShipmentFinalStatus($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::OBJECT_ID] == "" || $params[ParamKeys::SHIPMENT_STATUS] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $shipmentStatement = $this->db->prepare('SELECT * FROM cor_shipments WHERE SHIPMENT_ID = :objectId');
                        $shipmentStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                        $shipmentStatement->execute();
                        $details = $shipmentStatement->fetch(\PDO::FETCH_ASSOC);
                        if ($details) {
                            $this->db->beginTransaction();

                            $shipmentStatement = $this->db->prepare('UPDATE cor_shipments SET FINAL_STATUS = :status, UPDATED_DATE = :updateDate, PAYMENT_RECEIVED_DATE = :paymentReceiveDate WHERE SHIPMENT_ID = :objectId');
                            $shipmentStatement->bindParam(":status",  $params[ParamKeys::SHIPMENT_STATUS], \PDO::PARAM_STR);
                            $shipmentStatement->bindParam(":updateDate", $params[ParamKeys::UPDATED_DATE], \PDO::PARAM_STR);
                            $shipmentStatement->bindParam(":paymentReceiveDate", $params[ParamKeys::UPDATED_DATE], \PDO::PARAM_STR);
                            $shipmentStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                            $shipmentStatement->execute();

                            if ($this->db->commit()) {
                                return $this->sendHttpResponseMessage($response, "Shipment status updated");
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, 'Unable to fetch shipment');
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
//          $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function deleteShipment($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::SHIPMENT_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $proSql = $this->db->prepare('DELETE from cor_shipments WHERE SHIPMENT_ID = :shipmentId');
                            $proSql->bindParam(":shipmentId", $params[ParamKeys::SHIPMENT_ID], \PDO::PARAM_STR);
                            if($proSql->execute()){
                                $proShipmentLogs = $this->db->prepare('DELETE from cor_shipments_log WHERE SHIPMENT_ID = :shipmentId');
                                $proShipmentLogs->bindParam(":shipmentId", $params[ParamKeys::SHIPMENT_ID], \PDO::PARAM_STR);
                                $proShipmentLogs->execute();

                                $this->deleteShipmentImages($params[ParamKeys::SHIPMENT_ID]);

                                return $this->sendHttpResponseMessage($response, "Shipment deleted successfully.");
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function uploadShipmentImages($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::SHIPMENT_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $uploadedFiles = $request->getUploadedFiles();
                        if (!empty($uploadedFiles)) {
                            foreach ($uploadedFiles['SHIPMENT_IMAGES'] as $uploadedFile) {
                                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                    $filename = $this->moveUploadedFile('storage/shipments', $uploadedFile);

                                    $this->addShipmentImages($filename, $params[ParamKeys::SHIPMENT_ID]);
                                }
                            }

                            return $this->sendHttpResponseSuccess($response, 'Shipment images uploaded');
                        } else {
                            return $this->sendHTTPResponseError($response, 'No images uploaded');
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function createQbGroup($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::SHIPMENT_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $shipmentSql = "SELECT * FROM cor_shipments WHERE SHIPMENT_ID = :shipmentId LIMIT 1";
                        $shipmentStatement = $this->db->prepare($shipmentSql);
                        $shipmentStatement->bindParam(":shipmentId", $params[ParamKeys::SHIPMENT_ID], \PDO::PARAM_STR);
                        $shipmentStatement->execute();
                        $shipment = $shipmentStatement->fetch(\PDO::FETCH_ASSOC);

                        if($shipment) {
                            if (empty($shipment['QB_DIALOG_ID'])) {
                                $names[] = $this->getUser($shipment['CREATOR_ID'])['FULL_NAME'];

                                $user_id[] = $this->getUser($shipment['CREATOR_ID'])['QB_ID'];

                                $getShipmentAdminSql ="SELECT USER_ID FROM ptf_user_details WHERE SUPER_ADMIN = 1";
                                $getShipmentAdminStatement = $this->db->prepare($getShipmentAdminSql);
                                $getShipmentAdminStatement->execute();
                                $superAdmins = $getShipmentAdminStatement->fetchAll(\PDO::FETCH_ASSOC);

                                foreach ($superAdmins as $sa) {
                                    $user_id[] = $this->getUser($sa['USER_ID'])['QB_ID'];
                                    $names[] = $this->getUser($sa['USER_ID'])['FULL_NAME'];
                                }

                                $getShipperSql ="SELECT USER_ID FROM ptf_user_details WHERE IS_SHIPPER = 1";
                                $getShipperStatement = $this->db->prepare($getShipperSql);
                                $getShipperStatement->execute();
                                $shippers = $getShipperStatement->fetchAll(\PDO::FETCH_ASSOC);

                                foreach ($shippers as $sh) {
                                    $user_id[] = $this->getUser($sh['USER_ID'])['QB_ID'];
                                    $names[] = $this->getUser($sh['USER_ID'])['FULL_NAME'];
                                }

                                $this->qb_token = $this->_qbsession()->session->token;

                                if (!empty($this->qb_token)) {
                                    $qbGroup = $this->_createQbGroup(1, $names, $user_id);
                                }

                                if (!$qbGroup->errors) {
                                    $this->db->beginTransaction();
                                    $shipmentStatement = $this->db->prepare('UPDATE cor_shipments SET QB_DIALOG_ID = :qbGroupId WHERE SHIPMENT_ID = :shipmentId');
                                    $shipmentStatement->bindParam(":shipmentId", $params[ParamKeys::SHIPMENT_ID], \PDO::PARAM_STR);
                                    $shipmentStatement->bindParam(":qbGroupId", $qbGroup->_id, \PDO::PARAM_STR);
                                    $shipmentStatement->execute();

                                    if ($this->db->commit()) {
                                        $shipmentSql = "SELECT * FROM cor_shipments WHERE SHIPMENT_ID = :shipmentId LIMIT 1";
                                        $shipmentStatement = $this->db->prepare($shipmentSql);
                                        $shipmentStatement->bindParam(":shipmentId", $params[ParamKeys::SHIPMENT_ID], \PDO::PARAM_STR);
                                        $shipmentStatement->execute();
                                        $shipment = $shipmentStatement->fetch(\PDO::FETCH_ASSOC);

                                        return $this->sendHttpResponseSuccess($response, $shipment);
                                    }
                                } else {
                                    return $this->sendHTTPResponseError($response, $qbGroup->errors[0]);
                                }
                            } else {
                                return $this->sendHttpResponseSuccess($response, $shipment);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            //$this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    private function moveUploadedFile($directory, $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = rand(10000000,99999999);
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    private function notifySuperAdmins($shipmentId, $params, $msg) {
        // send notification
        $superAdmin = 1;
        $deviceTokensSql = "SELECT USER_ID, DEVICE_TOKEN FROM ptf_user_details WHERE SUPER_ADMIN = :superAdmin";
        $deviceTokenStatement = $this->db->prepare($deviceTokensSql);
        $deviceTokenStatement->bindParam(":superAdmin", $superAdmin);
        $deviceTokenStatement->execute();
        $superAdmins = $deviceTokenStatement->fetchAll(\PDO::FETCH_ASSOC);

        foreach($superAdmins as $key => $superAdmin) {
            $device_tokens[0] = $superAdmin[ParamKeys::DEVICE_TOKEN];
            $this->sendNotification($params[ParamKeys::CREATOR_ID], $superAdmin[ParamKeys::USER_ID],
                $shipmentId, 'shipment', $msg, $device_tokens, $params[ParamKeys::SHIPMENT_TITLE]);
        }
    }

    private function notifyShipmentCreator($shipmentId, $params, $msg) {
        $deviceTokensSql = "SELECT USER_ID, DEVICE_TOKEN FROM ptf_user_details WHERE USER_ID= :objectId";
        $deviceTokenStatement = $this->db->prepare($deviceTokensSql);
        $deviceTokenStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID]);
        $deviceTokenStatement->execute();
        $user = $deviceTokenStatement->fetch(\PDO::FETCH_ASSOC);
        $device_tokens[] = $user['DEVICE_TOKEN'];
        $this->sendNotification(0, $params[ParamKeys::USER_ID],
            $shipmentId, 'shipment', $msg, $device_tokens, $params[ParamKeys::SHIPMENT_STATUS]);

    }

    public function notifyShippers ($shipmentId, $params, $msg) {
        $shipper = 1;
        $deviceTokensSql = "SELECT USER_ID, DEVICE_TOKEN FROM ptf_user_details WHERE IS_SHIPPER = :shipper";
        $deviceTokenStatement = $this->db->prepare($deviceTokensSql);
        $deviceTokenStatement->bindParam(":shipper", $shipper);
        $deviceTokenStatement->execute();
        $shippers = $deviceTokenStatement->fetchAll(\PDO::FETCH_ASSOC);

        foreach($shippers as $key => $shipper) {
            $device_tokens[0] = $shipper[ParamKeys::DEVICE_TOKEN];
            $this->sendNotification(0, $shipper[ParamKeys::USER_ID],
                $shipmentId, 'shipment', $msg, $device_tokens, $params[ParamKeys::SHIPMENT_STATUS]);
        }

    }

    private function addShipmentImages($filename, $shipmentId) {
        if (!is_null($filename)) {
            $this->db->beginTransaction();
            $stm = $this->db->prepare('INSERT cor_shipments_images (SHIPMENT_ID, SHIPMENT_IMAGE) 
                                                                        VALUES (:shipmentId, :shipmentImage)');
            $stm->bindParam(":shipmentId", $shipmentId, \PDO::PARAM_STR);
            $stm->bindParam(":shipmentImage", $filename, \PDO::PARAM_STR);
            $stm->execute();
            $this->db->commit();
        }
    }

    private function deleteShipmentImages($shipmentId) {

        $shipmentImages = $this->db->prepare('SELECT SHIPMENT_IMAGE FROM cor_shipments_images WHERE SHIPMENT_ID = :shipmentId');
        $shipmentImages->bindParam(":shipmentId", $shipmentId, \PDO::PARAM_STR);
        $shipmentImages->execute();
        $images = $shipmentImages->fetchAll(\PDO::FETCH_ASSOC);

        if(!empty($images)) {
            foreach ($images as $image) {
                @unlink('storage/shipments/' . $image['SHIPMENT_IMAGE']);
            }
        }

        $proShipmentImages = $this->db->prepare('DELETE from cor_shipments_images WHERE SHIPMENT_ID = :shipmentId');
        $proShipmentImages->bindParam(":shipmentId", $shipmentId, \PDO::PARAM_STR);
        $proShipmentImages->execute();
    }

    private function getUser($user_id) {
        $taskSql = "SELECT * FROM ptf_user_details WHERE USER_ID IN (:userId)";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":userId", $user_id);
        $tasksStatement->execute();
        $result = $tasksStatement->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }
}