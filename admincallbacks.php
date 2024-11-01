<?php


class zightaccess_admincallbacks {


	public function __construct($zigaction) {
		if (method_exists($this, $zigaction)) $this->$zigaction();
	}


	public function update_htaccess() {
		global $zightaccess;
		if (!current_user_can('manage_options')) { wp_die('You are not allowed to do this.'); }
		check_admin_referer('zigpress_nonce');
		list($ok, $result) = array(true, 'OK|Changes saved and backup updated.');

		if ($ok) {
			if (!is_writeable($zightaccess->path_htaccess)) {
				list($ok, $result) = array(false, 'ERR|.htaccess is not writeable!');
			}
		}

		if ($ok) {
			$old_content = $zightaccess->get_htaccess_content();
			$new_content = stripslashes(trim($_POST['content']));
		}

		if ($ok) {
			if (!$zightaccess->save_htaccess_backup($old_content)) {
				list($ok, $result) = array(false, 'ERR|Backup could not be created so the .htaccess file was left unchanged.');
			}
		}

		if ($ok) {
			if (!$zightaccess->save_htaccess($new_content)) {
				list($ok, $result) = array(false, 'ERR|Backup was created but new .htaccess could not be saved.');
			}
		}

		if (ob_get_status()) ob_clean();
		wp_redirect($zightaccess->callback_url . '?page=' . $zightaccess->admin_page_name . '&r=' . base64_encode($result));
		exit();
	}


	public function restore_backup() {
		global $zightaccess;
		if (!current_user_can('manage_options')) { wp_die('You are not allowed to do this.'); }
		check_admin_referer('zigpress_nonce');
		list($ok, $result) = array(true, 'OK|Backup restored.');

		if ($ok) {
			if (!is_writeable($zightaccess->path_htaccess)) {
				list($ok, $result) = array(false, 'ERR|.htaccess is not writeable!');
			}
		}

		if ($ok) {
			if (!$zightaccess->htaccess_backup_exists()) {
				list($ok, $result) = array(false, 'ERR|No backup exists!');
			}
		}

		if ($ok) {
			$backup_content = $zightaccess->get_htaccess_backup_content();
			if (!$zightaccess->save_htaccess($backup_content)) {
				list($ok, $result) = array(false, 'ERR|New .htaccess could not be saved.');
			}
		}

		if (ob_get_status()) ob_clean();
		wp_redirect($zightaccess->callback_url . '?page=' . $zightaccess->admin_page_name . '&r=' . base64_encode($result));
		exit();
	}


	public function delete_backup() {
		global $zightaccess;
		if (!current_user_can('manage_options')) { wp_die('You are not allowed to do this.'); }
		check_admin_referer('zigpress_nonce');
		list($ok, $result) = array(true, 'OK|Backup deleted.');

		if ($ok) {
			if (!is_writeable($zightaccess->path_htaccess_backup)) {
				list($ok, $result) = array(false, 'ERR|Backup is not writeable!');
			}
		}

		if ($ok) {
			$zightaccess->delete_htaccess_backup();
		}

		if (ob_get_status()) ob_clean();
		wp_redirect($zightaccess->callback_url . '?page=' . $zightaccess->admin_page_name . '&r=' . base64_encode($result));
		exit();
	}


} # END OF CLASS


# EOF
