<?php
namespace Esmi\Web;
use KMFA\kmfa;

trait AppUtils
{

    public function __construct() {
        $this->org = new kmfa();
        parent::__construct($this->segname);
    }
    protected function rules() {
        return $this->checks;
    }

    protected function chkError() {

        $request = $this->request;
    
        if ($request->isMethod('POST')) {
                    
            $validator = new Validator();
            
            $validation = $validator->make(
                $_POST['data']['row'],
                $this->rules(),
                $this->ruleMessages()
            );
            $validation->setAliases($this->getAlias());

            $validation->validate();

            if ($validation->fails()) 
                $this->errors = $validation->errors();
        }
        return $this->errors;
    }
    function columns() { }
    protected function baseTools() {
        return [
            [   //button: newData()
                'display' => '新增', 'input' => "button",
                'attrs'  => 'class="easyui-linkbutton" iconCls="icon-add" plain="false"',
                'action' => 'onclick="newData()"'
            ], 
            [   //button: modifyData()
                'display' => '修改', 'input' => "button",
                'attrs'  => 'class="easyui-linkbutton" iconCls="icon-add" plain="false"',
                'action' => 'onclick="modifyData()"'
            ], 
            [   //button : destroyData()
                'input' => "button", 'display' => '刪除', 
                'attrs'  => 'class="easyui-linkbutton" iconCls="icon-remove" plain="false"',
                'action' => 'onclick="destroyData()"'
            ],
        ];
    }
    protected function tools() {
        return $this->baseTools();
    }
	protected function panel() {	return [];    }
    protected function grid($o) {   
        return [
            'id' => 'dg',
            'title' => $o->title,
            'class' => "easyui-datagrid",
            //'style' => "height:400px",
            'url' => $o->url,
            'toolbar' => "#toolbar",
            'sortName' => "uniqueno",

            'sortOrder' => "asc",
            'remoteSort' => "false",
            //'rownumbers' => "true",
            //'fitColumns' => "true",

            'singleSelect' => "true",
            'checkOnSelect' => "false",
            //pageAttrs
            //'pageAttrs' => 'pagination:true,pagePosition:\'top\',pageSize:15,pageList:[10,15,20,30,50,100]',
            'pageAttrs' => 'pagination:true,pagePosition:\'top\',pageSize:15,pageList:[10,15,20,30,50,100],queryParams:{method:\'getall\'}',
            //'pagePosition' => "top",
            //'pagination' => "true", 'pagePosition' => "top", 'pageSize' => 'pageSize:15', 'pageList' => 'pageList:[10,15,20]',

        ];
    }
    
}