<?php
class pBdb{
    //版本号
    var $VERSION = "psebdb-0-1";
    var $config = array(
    //最大容量
    	"LIMIT"=>1000000,
    //块大小
        "BLOCKSIZE"=>8,
    );

    var $block = array();
    //
    var $hidx;

    public function __construct()
    {

    }


    /**
     * 打开一个数据库文件，如果不存在则自动创建一个
     * @param directory string 数据库文件目录
     * @return true or false
     */
    public function open($directory)
    {
        if(file_exists($directory."/psebdb.idx") == false)
        {
            $this->create($directory);
        }
        else{
            $this->hidx = fopen($directory."/psebdb.idx","r+");
        }

    }

    public function cldNum()
    {
        $cldnum = intval(($this->config['BLOCKSIZE']*1024)/64);
        return $cldnum;
    }

    public function autoconf()
    {
        echo "设计容量".$this->config['LIMIT']."<br>";
        echo "文件块大小".$this->config['BLOCKSIZE']."KB <br>";
        $cldNum = $this->cldNum();
        echo "每个节点最多有".$cldNum."个子节点<br>";
        $cap = $cldNum;
        $depth = 0;
        while($cap<$this->config['LIMIT'])
        {
            $cap *= $cldNum;
            $depth++;
        }
        echo "需要 $depth 层<br>";
        $this->config['DEPTH'] = $depth;
    }

    /**
     * 创建一个空的数据库
     * @param directory string
     * @return true or false
     */
    public function create($directory)
    {
        if(file_exists($directory) == false)
        {
            return false;
        }
        echo "create ...<br>";
        $this->autoconf();
        $this->hidx = fopen($directory."/psebdb.idx","w+");
        fseek($this->hidx, 512, SEEK_SET);
        $buf = pack("I",0);
        fwrite($this->hidx, $buf,4);
    }

    /**
     * 创建一个数据块
     * struct blockInfo {
     *      uint16 free
     * 		uint16 nodeNum;     //块内节点个数
     *      uint16 node
     * }
     */
    public function createBlock()
    {
        fseek($this->hidx, 512, SEEK_SET);
        $buf = fread($this->hidx, 4);
        if($buf == false)
        {
            return false;
        }
        else{
            $data = unpack("Iblocknum", $buf);
            $blocknum = $data[blocknum];
            unset($data);
            unset($buf);
            echo "当前Block数量 $blocknum 个<br>";
        }
        $blocknum++;
        fseek($this->hidx, 512, SEEK_SET);
        $buf = pack("I",$blocknum);
        fwrite($this->hidx, $buf,4);
        return $blocknum-1;
    }

    /**
     * 创建非叶子节点
     * node = array {
     *      'blkid'=>143,                   //节点blkid
     *      'level'=>2,				        //节点位于第几层
     *      'children'=>array(		        //子节点
     *      	0=>array('key1'=>'bldid1'),
     *		    1=>array('key2'=>'bldid2'),
     *      );              //子节点数量
     *
     * }
     * @param key string
     */
    public function createNode()
    {
        $blkid = $this->createBlock();
        if($blkid === false)
        {
            return false;
        }
        if($blkid === 0)
        {
            echo "创建根结点<br>";

        }
        $node = array('blkid'=>$blkid);
    }
    
    /**
     *	在指字的子树中查找关键字 
     * @param string $key
     */
    public function find($node, $key)
    {
        $res = array("id"=>-1);
        if($node['level'] >= $this->config['DEPTH'])
        {
            echo "已到达最低层,没有找到指定关键字<br>";
            return false;
        }
        $cldnum = sizeof($node['children']);
        if(cldnum==0)
        {
            echo "错误，当前节点没有子节点<br>";
            return false;
        }
        //
        $leftkey = $node['children'][0];
        if(strcmp($key,$leftkey)<0)
        {
            echo "错误，目标不在当前节点范围内<br>";
            return false;
        }
        $rightkey = $node['children'][$cldnum];
        if(strcmp($key,$rightkey)>0)
        {
            $res['left'] = $rightkey;
            $res['id'] = $cldnum;
            return false;
        }
        //折半查找，由小到大
        $left = 0;
        $right = $cldnum;
        $mid = intval($right/2);
        $leftkey = $node['children'][$left];
        $rightkey = $node['children'][$right];
        $midkey = $node['children'][$mid];
        while($node['children'][$mid] != $key)
        {
            if(strcmp($midkey,$key)>0)
            {
                $right = $mid;
                $rightkey = $node['children'][$right]; 
            }
            else{
                $left = $mid;
                $leftkey = $node['children'][$left]; 
            }
        }
    }

    public function addChild($node,$cldnode)
    {
        if($node['level'] >= $this->config['DEPTH'])
        {
            echo "已到达最低层<br>";
            return false;
        }
        $cldnode = $node['level']+1;

    }

    public function put($key,$value=null)
    {
        $this->createNode($key);
    }
}


$bdb = new pBdb();
$bdb->open("./data/");
$bdb->put("lala");