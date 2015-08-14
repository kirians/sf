<?php
class ModerationController extends AdminController {

//отправка писем
     public function SendMail($email="",  $subject='', $message=""){

        $mail = new YiiMailMessage;
        $mail->setBody($message, 'text/html');
        $mail->subject = $subject;
				
        $mail->addTo($email);
        $mail->from = Yii::app()->params['regEmail'];
		$mail->setFrom(array( 'reg@stolica-skidok.ru' => 'Столица Скидок' ));
	
        return Yii::app()->mail_reg->send($mail);
	 }
       /*организаторы (Владельцы)*/
    public function actionOwner(){
		//список городов
		$city = Yii::app()->session->get("user_city");
		$city = 2;
		$cityFilters = CityForFilters::model()->findAll();
		$data = Coupon::model()->findAll(array('order'=>'created DESC'));
	 
		$this->render('owner', array('cityFilters'=>$cityFilters,
					     'data'=>$data
					     ));
  
  }
  //просмотр организатора
    public function actionOneVendor(){
		 if(isset($_GET['id'])){
		 	 $id = $_GET['id'];
			 $data = Coupon::model()->findAll(array('condition'=>'user = '.$id));
		}
		$this->render('oneVendor', array( 'data'=>$data));
	}
  //публикация выбраных заявок
 	public function actionPublic(){
		if(isset($_POST['cupid'])){
			$cup = $_POST['cupid']; //получаю массив ид купонов на публикацию
			$mail = array();
			$i=0;
			foreach($cup as $k => $c):
				//обновляю статус купона на активный
				$a=Coupon::model()->updateByPk($c,array('status'=>"Active"));
				
				//ищу к какому разделу принадлежит купон
				$sec = CouponSection::model()->findByAttributes(array('id_coupon'=>$c));
				//ищу запросы по найденому разделу
				$rs = RequestSection::model()->findAll(array('condition'=>'id_section = '.$sec['id_section']));
				//отправляю письма на запросы
				if($rs){
				    foreach($rs as $idreq):
					    $request_date = $idreq->Request->validTill;
					    if($request_date >= date("Y-m-d")){
						    //$this->repMails($idreq->Request->email,$c);
						    $mail[$i]['email']=$idreq->Request->email;
						    $mail[$i]['id']=$c;
						    $i++;
					    }
					    
				    endforeach;
				}
			endforeach;
			//Bug::pre($mail);
			foreach($mail as $m):
			      $this->repMails($m['email'],$m['id']);
			endforeach;
			
			
	     	echo "ok";
		}
		
	}
	//отправка писем пользователям, которые сделали заявки на разделы данных купонов
	
	public function repMails($email,$id){
		//получаю шаблон письма
		$sms = Mails::model()->findByPk(1);
		if($sms){
			//вставляю  тело
			$html = $sms['body'];
			//тема
		     $subject = $sms['theme'];
			//нахожу нужный купон
			$coupon = Coupon::model()->findByPk($id); 
			//название купона
		    $product = $coupon['title'];
			//скидка
			$sale = $coupon['discount'];
			//цен до скидки
			$before =  $coupon['discountPrice'] ." рублей";
			if($before ==0){
				$before = "Не ограничена";
				$after = "Не ограничена";
			}
			else{
			   //цена после скидки
				$after = round(($before - ($sale*$before/100)),2);
				$after .=" рублей";
			}
			//ссылка на купон
			$link = $_SERVER['HTTP_HOST'].Yii::app()->createUrl("/coupon",array('id' => $coupon['id'],
																				'title'=>$coupon['title']));
			$link = "<a href='http://".$link."'>".$link."</a>";
			//подставляю в шаблон
			$html = str_replace('[product]',$product,$html);
			$html = str_replace('[before]',$before,$html);
			$html = str_replace('[after]',$after,$html);
			$html = str_replace('[sale]',$sale,$html);
			$html = str_replace('[link]',$link,$html);
			
			//отправляем письмо
			$this->sendMail($email,$subject,$html);
			
		}
	}
	
	
}