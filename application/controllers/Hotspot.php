<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hotspot extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		// 1. memuat library routerosapi
		$this->load->library('routerosapi');
	}
	// 2a. deklarasi variable hostname, username dan password
	var $hostname_mikrotik = '192.168.0.1';
	var $username_mikrotik = 'admin';
	var $password_mikrotik = '';
	
	public function index()
	{
		// 2b. koneksi ke mikrotik menggunakan method connect
		if ($this->routerosapi->connect($this->hostname_mikrotik, $this->username_mikrotik, $this->password_mikrotik))
		{
			// 3. Mengeksekusi perintah Mikrotik CLI untuk menampilkan informasi keseluruhan data user hotspot
			$this->routerosapi->write('/ip/hotspot/user/getall');
			
			// 4. Membaca hasil dari eksekusi perintah Mikrotik
			$users = $this->routerosapi->read();
			
			// 5. Memutuskan koneksi dari Mikrotik RouterOS
			$this->routerosapi->disconnect();						
			
			$total_results = count($users);
			if ($total_results > 0){
				$data['total_results'] = $total_results;
				$table  = '<table class="table table-bordered table-hover">';
				$table .= '<thead>';
				$table .= '<tr>';
				$table .= '<th class="text-center">No.</th>';
				$table .= '<th class="text-center">Server</th>';
				$table .= '<th class="text-center">Name</th>';
				$table .= '<th class="text-center">Password</th>';
				$table .= '<th class="text-center">MAC Address</th>';
				$table .= '<th class="text-center">Profile</th>';
				$table .= '<th class="text-center">Comment</th>';
				$table .= '<th class="text-center">Disabled</th>';
				$table .= '<th class="text-center">Action</th>';
				$table .= '</tr>';
				$table .= '</thead>';
				$i = 1;
				foreach ($users as $user){	
					$table .= '<tr>';
					$table .= '<td class="col-md-1 text-center">'.$i.'.</td>';
					if (isset($user['server'])){
						$table .= '<td>'.$user['server'].'</td>';
					}else{
						$table .= '<td>&nbsp;</td>';
					}
					$table .= '<td class="col-md-1 text-center">'.$user['name'].'</td>';
					$table .= '<td class="col-md-1 text-center">'.$user['password'].'</td>';
					if (isset($user['mac-address'])){
						$table .= '<td>'.$user['mac-address'].'</td>';
					}else{
						$table .= '<td>&nbsp;</td>';
					}
					$table .= '<td class="col-md-1 text-center">'.$user['profile'].'</td>';
					if (isset($user['comment'])){
						$table .= '<td>'.$user['comment'].'</td>';					
					}else{
						$table .= '<td>&nbsp;</td>';
					}
					$table .= '<td class="col-md-1 text-center">'.$user['disabled'].'</td>';						
					$table .= '<td>';
					$table .= anchor('hotspot/update/'.$user['.id'],'<button type="button" class="btn btn-success btn-sm">
					                                 <span class="glyphicon glyphicon-edit"></span> Update</button>').' ';
					$table .= anchor('hotspot/remove/'.$user['.id'],'<button type="button" class="btn btn-danger btn-sm">
					                                <span class="glyphicon glyphicon-minus"></span> Remove</button>').' ';
					if ($user['disabled'] == 'false'){
						$table .= anchor('hotspot/disable/'.$user['.id'],'<button type="button" class="btn btn-warning btn-sm">
						                                   <span class="glyphicon glyphicon-remove"></span> Disable</button>');
					}else{
						$table .= anchor('hotspot/enable/'.$user['.id'],'<button type="button" class="btn btn-info btn-sm">
						                                    <span class="glyphicon glyphicon-ok"></span> Enable</button>');
					}
					$table .= '</td>';				
					$table .= '</tr>';
					$i++;
				}
				$table .= '</table>';
				$data['table'] = $table;
			}
			$data['container'] = 'hotspot/hotspot';	
			$data['link'] = array('link_tambah' => anchor('hotspot/add', '<button type="button" class="btn btn-primary btn-sm">
			                                                 <span class="glyphicon glyphicon-plus"></span> Tambah</button>'));
			$this->load->view('template', $data);
		}
	}
	
	public function disable($id)
	{
		if ($this->routerosapi->connect($this->hostname_mikrotik, $this->username_mikrotik, $this->password_mikrotik)){
			$this->routerosapi->write('/ip/hotspot/user/disable',false);
			$this->routerosapi->write('=.id='.$id);
			$hotspot_users = $this->routerosapi->read();
			$this->routerosapi->disconnect();	
			$this->session->set_flashdata('message','Data user tersebut berhasil dinonaktifkan!');
			redirect('hotspot');
		}
	}
	
	public function enable($id)
	{
		if ($this->routerosapi->connect($this->hostname_mikrotik, $this->username_mikrotik, $this->password_mikrotik)){
			$this->routerosapi->write('/ip/hotspot/user/enable',false);
			$this->routerosapi->write('=.id='.$id);
			$hotspot_users = $this->routerosapi->read();
			$this->routerosapi->disconnect();	
			$this->session->set_flashdata('message','Data user tersebut berhasil diaktifkan!');
			redirect('hotspot');
		}
	}
	
	public function remove($id)
	{
		if ($this->routerosapi->connect($this->hostname_mikrotik, $this->username_mikrotik, $this->password_mikrotik)){
			$this->routerosapi->write('/ip/hotspot/user/remove',false);
			$this->routerosapi->write('=.id='.$id);
			$hotspot_users = $this->routerosapi->read();
			$this->routerosapi->disconnect();	
			$this->session->set_flashdata('message','Data user tersebut berhasil dihapus!');
			redirect('hotspot');
		}
	}
	
	public function add(){
		$data['container'] = 'hotspot/hotspot_form';
		$data['form_action'] = site_url('hotspot/add');				
				
		$this->form_validation->set_rules('name', 'Name', 'required');
		$this->form_validation->set_rules('profile', 'Profile', 'required');		
		$this->form_validation->set_rules('disabled', 'Disabled', 'required');	
				
		if ($this->form_validation->run() == TRUE)
		{
			$server = $this->input->post('server');			
			$name = $this->input->post('name');
			$password = $this->input->post('password');
			$mac_address = $this->input->post('mac_address');
			$profile = $this->input->post('profile');
			$comment = $this->input->post('comment');
			$disabled = $this->input->post('disabled');
			if ($this->routerosapi->connect($this->hostname_mikrotik, $this->username_mikrotik, $this->password_mikrotik)){
				$this->routerosapi->write('/ip/hotspot/user/add',false);				
				$this->routerosapi->write('=server='.$server, false);							
				$this->routerosapi->write('=name='.$name, false);
				if (!empty($password)){
					$this->routerosapi->write('=password='.$password, false);     				
				}
				if (!empty($mac_address)){
					$this->routerosapi->write('=mac-address='.$mac_address, false);	
				}				
				$this->routerosapi->write('=profile='.$profile, false);
				if (!empty($comment)){
					$this->routerosapi->write('=comment='.$comment, false);	
				}		
				$this->routerosapi->write('=disabled='.$disabled);				
				$hotspot_users = $this->routerosapi->read();
				$this->routerosapi->disconnect();	
				$this->session->set_flashdata('message','Data user hotspot tersebut berhasil ditambahkan!');
				redirect('hotspot');
			}
		}else{
			$data['default']['server'] = $this->input->post('server');
			$data['default']['name'] = $this->input->post('name');
			$data['default']['password'] = $this->input->post('password');
			$data['default']['mac_address'] = $this->input->post('mac_address');
			$data['default']['profile'] = $this->input->post('profile');
			$data['default']['comment'] = $this->input->post('comment');
			$data['default']['disabled'] = $this->input->post('disabled');
		}
		$this->load->view('template', $data);				
	}
	
	public function update($id){
		$data['container'] = 'hotspot/hotspot_form';
		$data['form_action'] = site_url('hotspot/process_update');		
		
		if ($this->routerosapi->connect($this->hostname_mikrotik, $this->username_mikrotik, $this->password_mikrotik)){
			$this->routerosapi->write("/ip/hotspot/user/print", false);			
			$this->routerosapi->write("=.proplist=.id", false);
			$this->routerosapi->write("=.proplist=server", false);
			$this->routerosapi->write("=.proplist=name", false);
			$this->routerosapi->write("=.proplist=password", false);		
			$this->routerosapi->write("=.proplist=mac-address", false);
			$this->routerosapi->write("=.proplist=profile", false);
			$this->routerosapi->write("=.proplist=comment", false);		
			$this->routerosapi->write("=.proplist=disabled", false);		
			$this->routerosapi->write("?.id=$id");
					
			$hotspot_user = $this->routerosapi->read();

			foreach ($hotspot_user as $row)
			{
				if (isset($row['server'])){
					$server = $row['server'];
				}else{
					$server = '';
				}
				
				$name = $row['name'];			
				$password = $row['password'];
				
				if (isset($row['mac-address'])){
					$mac_address = $row['mac-address'];			
				}else{
					$mac_address = '';
				}
				
				$profile = $row['profile'];
				
				if (isset($row['server'])){
					$comment = $row['comment'];
				}else{
					$comment = '';
				}
				$disabled = $row['disabled'];

				if ($disabled == 'true')
				{
					$disabled='yes';
				}else{
					$disabled='no';
				}
			}
			$this->routerosapi->disconnect();
			
			$this->session->set_userdata('id',$id);
			
			$data['default']['server'] = $server;
			$data['default']['name'] = $name;			
			$data['default']['password'] = $password;
			$data['default']['mac_address'] = $mac_address;
			$data['default']['profile'] = $profile;
			$data['default']['comment'] = $comment;
			$data['default']['disabled'] = $disabled;
		}
		$this->load->view('template', $data);
	}
	
	public function process_update(){
		$data['container'] = 'hotspot/hotspot_form';
		$data['form_action'] = site_url('hotspot/process_update');	

		$this->form_validation->set_rules('name', 'Name', 'required');
		$this->form_validation->set_rules('profile', 'Profile', 'required');		
		$this->form_validation->set_rules('disabled', 'Disabled', 'required');	
		
		if ($this->form_validation->run() == TRUE)
		{
			$server = $this->input->post('server');			
			$name = $this->input->post('name');			
			$password = $this->input->post('password');
			if (empty($password)){
				$password = '';
			}			
			$mac_address = $this->input->post('mac_address');
			if (empty($mac_address)){
				$mac_address = '00:00:00:00:00:00';
			}
			$profile = $this->input->post('profile');
			$comment = $this->input->post('comment');
			$disabled = $this->input->post('disabled');
			
			if ($this->routerosapi->connect($this->hostname_mikrotik, $this->username_mikrotik, $this->password_mikrotik)){
				$this->routerosapi->write('/ip/hotspot/user/set',false);				
				$this->routerosapi->write('=.id='.$this->session->userdata('id'),false);
				$this->routerosapi->write('=server='.$server,false);										
				$this->routerosapi->write('=name='.$name, false);				
				$this->routerosapi->write('=password='.$password, false);    				
				$this->routerosapi->write('=mac-address='.$mac_address, false);								
				$this->routerosapi->write('=profile='.$profile, false);				
				$this->routerosapi->write('=comment='.$comment, false);						
				$this->routerosapi->write('=disabled='.$disabled);				
								
				$hotspot_users = $this->routerosapi->read();
				$this->routerosapi->disconnect();	
				$this->session->unset_userdata('id');
				$this->session->set_flashdata('message','Data user hotspot tersebut berhasil diubah!');
				redirect('hotspot');				
			}	
		}else{
			$data['default']['server'] = $this->input->post('server');
			$data['default']['name'] = $this->input->post('name');
			$data['default']['password'] = $this->input->post('password');
			$data['default']['mac_address'] = $this->input->post('mac_address');
			$data['default']['profile'] = $this->input->post('profile');
			$data['default']['comment'] = $this->input->post('comment');
			$data['default']['disabled'] = $this->input->post('disabled');
		}
		$this->load->view('template', $data);		
	}	
}
