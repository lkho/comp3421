<?php

/**
 * comp3421
 * Created by LKHO.
 * Date: 14/12/2015 16:33
 */
abstract class CrudController extends CI_Controller
{

    public $table;
    public $fields;
    public $title;
    public $view;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function _remap($action, $args)
    {
        $action1 = $action . '_' . $this->input->method();
        if (method_exists($this, $action1)) {
            return call_user_func_array(array($this, $action1), $args);
        } else if (method_exists($this, $action)) {
            return call_user_func_array(array($this, $action), $args);
        } else {
            $this->output->set_status_header(405);
            $this->output->set_output('Method ' . $action . ':' . $this->input->method(TRUE)
                . ' is not allowed.<br><a href="javascript:history.back()">Back</a>');
        }
    }

    protected function processDataSource($fields, $action)
    {
        foreach ($fields as $k => &$v) {
            if (isset($v['values_source'])) {
                $v['values'] = call_user_func($v['values_source'], $action, $this->input->method());
            }
        }

        return $fields;
    }

    public function index()
    {
        check_access(TRUE);

        $data = array(
            'title'      => $this->title,
            'menu'       => $this->load->view('menu', NULL, TRUE),
            'detail_url'   => site_url(uri_string() . '/detail/{id}'),
            'create_url' => site_url(uri_string() . '/create'),
            'edit_url'   => site_url(uri_string() . '/edit/{id}'),
            'delete_url' => site_url(uri_string() . '/delete/{id}'),
            'fields'     => $this->processDataSource($this->fields, __FUNCTION__),
        );

        $r = $this->db->get($this->table);
        $data['data'] = $r->result_array();

        $this->load->view($this->view[__FUNCTION__], $data);
    }

    public function detail($id)
    {
        check_access(TRUE);

        $data = array(
            'title'      => $this->title,
            'menu'       => $this->load->view('menu', NULL, TRUE),
            'edit_url'   => site_url(dirname(uri_string()) . '/edit/{id}'),
            'delete_url' => site_url(uri_string()),
            'fields'     => $this->processDataSource($this->fields, __FUNCTION__),
        );

        $r = $this->db->where('id', $id)->get($this->table);
        $r = $r->result_array();
        $data['data'] = reset($r);

        $this->load->view($this->view[__FUNCTION__], $data);
    }

    public function create()
    {
        check_access(TRUE, TRUE);

        $data = array(
            'title'  => 'Create ' . $this->title,
            'menu'   => $this->load->view('menu', NULL, TRUE),
            'button' => 'Create',
            'form'   => array(
                'action' => site_url(uri_string()),
                'method' => 'post',
            ),
            'fields' => $this->processDataSource($this->fields, __FUNCTION__),
        );

        $this->load->view($this->view[__FUNCTION__], $data);
    }

    public function create_post()
    {
        check_access(TRUE, TRUE);

        if ($this->db->insert($this->table, $this->input->post()) !== FALSE) {
            $create = json_encode(site_url(uri_string()));
            $list = json_encode(site_url(dirname(uri_string())));

            $this->output->append_output(
                "<script>
                if (confirm('Create Success!\\nClick OK to add more or Cancel to go back to the listing.'))
                    window.location = $create;
                else
                    window.location = $list;
                </script>"
            );
        } else {
            $this->output->set_status_header(500);
            $this->load->view('menu');
            $this->db->display_error();
        }
    }

    public function edit($id)
    {
        check_access(TRUE, TRUE);

        $data = array(
            'title'  => 'Edit ' . $this->title,
            'menu'   => $this->load->view('menu', NULL, TRUE),
            'button' => 'Update',
            'form'   => array(
                'action' => site_url(uri_string()),
                'method' => 'post',
            ),
            'fields' => $this->processDataSource($this->fields, __FUNCTION__),
        );

        $r = $this->db->where('id', $id)->get($this->table);
        $r = $r->result_array();
        $data['data'] = reset($r);

        $this->load->view($this->view[__FUNCTION__], $data);
    }

    public function edit_post($id)
    {
        check_access(TRUE, TRUE);

        if ($this->db->where('id', $id)->update($this->table, $this->input->post()) !== FALSE) {
            $list = json_encode(site_url(dirname(uri_string())));

            $this->output->append_output(
                "<script>alert('Update Success!');window.location = $list;</script>"
            );
        } else {
            $this->output->set_status_header(500);
            $this->load->view('menu');
            $this->db->display_error();
        }
    }

    public function delete($id)
    {
        check_access(TRUE, TRUE);

        $list = site_url(dirname(dirname(uri_string())));
        $this->load->view('confirm', array(
            'msg'        => 'Are you sure to delete the item? This cannot be undone!',
            'form'       => array('method' => 'post'),
            'cancel_url' => $list,
            'color'      => 'red',
        ));
    }

    public function delete_post($id)
    {
        check_access(TRUE, TRUE);

        if ($this->db->where('id', $id)->delete($this->table) !== FALSE) {
            $list = json_encode(site_url(dirname(dirname(uri_string()))));

            $this->output->append_output(
                "<script>alert('Delete Success!');window.location = $list;</script>"
            );
        } else {
            $this->output->set_status_header(500);
            $this->load->view('menu');
            $this->db->display_error();
        }
    }
}
