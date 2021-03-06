<?php defined('IN_APP') or die('Get out of here');

class Admin_model extends Model {
	public function __construct() {
		parent::__construct();
	}
	
	public function findUser($username, $password) {
		$password = Crypt::encrypt($password);
		
		return $this->db->select('*')->from('users')->where(array(
			'username' => $username,
			'password' => $password
		))->fetch();
	}
	
	public function findProduct($id) {
		return $this->_format($this->db->select('*')->from('products')->where(array(
			'id' => $id
		))->fetch());
	}
	
	public function allProducts() {
		$products = $this->db->select('*')->from('products')->fetch(false, true);
		$return = array();
		
		foreach($products as $id => $product) {
			$return[$id] = $this->_format($product);
		}
		
		return $return;
	}
	
	public function insertProduct($product) {
		$insert = $this->db->insert('products')->values($product)->go();
		
		if($insert === false) {
			return false;
		}
		
		return $this->db->select('*')->from('products')->order('id desc')->fetch(1);
	}
	
	public function enablePlugin($name) {
		$plugins = Config::get('plugins') . ',';
		$name = preg_replace('/[^a-zA-Z]+/', '', $name);
		
		if(strpos($plugins, $name . ',') !== false) {
			return true;
		}
		
		return $this->_updatePlugin(trim($plugins . $name, ','));
	}
	
	public function disablePlugin($name) {
		$plugins = Config::get('plugins') . ',';
		$name = preg_replace('/[^a-zA-Z]+/', '', $name);
		
		if(strpos($plugins, $name) === false) {
			return true;
		}
		
		$val = (string) substr(str_replace($name . ',', '', $plugins), 0, -1);
				
		return $this->_updatePlugin($val);
	}
	
	public function update($id, $product) {
		$product['product_id'] = $product['id'];
		$product['visible'] = $product['visible'] === 'yes';
		unset($product['id']);
		
		return !$this->db->update('products')->set($product)->where(array('id' => $id))->go();
	}
	
	private function _updatePlugin($val) {
		return Config::save('plugins', $val);
	}
	
	private function _format($product) {
		$product->oos = $product->current_stock < 1;
		
		
		$product->tags = array();
			
		if($product->oos) {
			$product->tags[] = 'Out of stock';
		} else if(($product->current_stock / $product->total_stock) < .1) {
			$product->tags[] = 'Low stock';
		}
		
		if($product->discount > 0) {
			$product->tags[] = 'Discounted ' .  $product->discount . '%';
		}
		
		if(!$product->visible) {
			$product->tags[] = 'Hidden';
		}
		
		return $product;
	}
}