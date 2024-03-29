<?php
namespace frontend\models;

use common\models\User;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use Yii;
use yii\helpers\Html;
use common\helpers\Media;

/**
 * This is the model class for table "{{%article}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $title
 * @property string $summary
 * @property string $content
 * @property integer $status
 * @property integer $category
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property User $user
 */
class Article extends ActiveRecord
{
    const STATUS_DRAFT = 1;
    const STATUS_PUBLISHED = 2;
    public $author;
    public $_status;
    public $_category;
    const CATEGORY_ECONOMY = 1;
    const CATEGORY_SOCIETY = 2;
    const CATEGORY_SPORT = 3;
    const CATEGORY_URL = "/articles/";
    public $image;
    public $attachments = false;
    public function init(){
       parent::init();
        $this->on(self::EVENT_AFTER_FIND,[$this,'setCustomProps']);
    }

    public function setCustomProps()
    {
        $this->author = $this->getAuthorName();
        $this->_status = $this->getStatusName();
        $this->_category = $this->getCategoryName();
        $files = Media::getFilesInfo($this);
        if (sizeof($files) > 0) {
            $this->attachments = true;//all attachment and their fileinfo
        }
    }

    /**
     * Declares the name of the database table associated with this AR class.
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%article}}';
    }

    /**
     * Returns the validation rules for attributes.
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['user_id', 'title', 'summary', 'content', 'status'], 'required'],
            [['user_id', 'status', 'category'], 'integer'],
            [['summary', 'content'], 'string'],
            [['attachments'],'safe'],
            [['title'], 'string', 'max' => 255]
        ];
    }

    /**
     * Returns a list of behaviors that this component should behave as.
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            'fileBehavior' => [
                'class' => \sampa\media\behaviors\FileBehavior::className(),

            ]
        ];
    }

    /**
     * Returns the attribute labels.
     *
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'Author'),
            'author' => Yii::t('app', 'Author'),
            'title' => Yii::t('app', 'Title'),
            'summary' => Yii::t('app', 'Summary'),
            'content' => Yii::t('app', 'Content'),
            'status' => Yii::t('app', 'Status'),
            'category' => Yii::t('app', 'Category'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'attachments' => Yii::t('app', 'Attachments')
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * Gets the id of the article creator.
     * NOTE: needed for RBAC Author rule.
     * 
     * @return integer
     */
    public function getCreatedBy()
    {
        return $this->user_id;
    }

    /**
     * Gets the author name from the related User table.
     * 
     * @return mixed
     */
    public function getAuthorName()
    {
        return $this->user->username;
    }

    /**
     * Returns the article status in nice format.
     *
     * @param  null|integer $status Status integer value if sent to method.
     * @return string               Nicely formatted status.
     */
    public function getStatusName($status = null)
    {
        $status = (empty($status)) ? $this->status : $status ;

        if ($status === self::STATUS_DRAFT)
        {
            return Yii::t('app', 'Draft');
        }
        else
        {
            return Yii::t('app', 'Published');
        }
    }

    public function getUserList(){
        $userArray = [
            $this->user_id => $this->author
        ];
        return $userArray;
    }
    /**
     * Returns the array of possible article status values.
     *
     * @return array
     */

    public function getStatusList()
    {
        $statusArray = [
            self::STATUS_DRAFT     => Yii::t('app', 'Draft'),
            self::STATUS_PUBLISHED => Yii::t('app', 'Published'),
        ];

        return $statusArray;
    }

    /**
     * Returns the article category in nice format.
     *
     * @param  null|integer $category Category integer value if sent to method.
     * @return string                 Nicely formatted category.
     */
    public function getCategoryName($category = null)
    {
        $category = (empty($category)) ? $this->category : $category ;

        if ($category === self::CATEGORY_ECONOMY)
        {
            return Yii::t('app', 'Economy');
        }
        elseif ($category === self::CATEGORY_SOCIETY)
        {
            return Yii::t('app', 'Society');
        }
        else
        {
            return Yii::t('app', 'Sport');
        }
    }
    /**
     * Returns the article category as int.
     *
     * @param  null|string $category Category string value if sent to method.
     * @return int category number
     */
    public static function getCategoryId($category)
    {
        $category = ucfirst($category);
        if (Yii::t('app', 'Economy') === $category)
        {
            return self::CATEGORY_ECONOMY;
        }
        elseif (Yii::t('app', 'Society') === $category)
        {
            return self::CATEGORY_SOCIETY;
        }
        else
        {
            return self::CATEGORY_SPORT;
        }
    }
    /**
     * Returns the array of possible article category values.
     *
     * @return array
     */
    public function getCategoryList()
    {
        $statusArray = [
            self::CATEGORY_ECONOMY => Yii::t('app', 'Economy'),
            self::CATEGORY_SOCIETY => Yii::t('app', 'Society'),
            self::CATEGORY_SPORT   => Yii::t('app', 'Sport'),
        ];

        return $statusArray;
    }

    /**
     * Returns an array of the categories in with links
     * @param $array an array of the available categories
     */

    public static function getCategoryListItems($array=null,$type="li",$options=[],$linkOptions=[])
    {
        $model = new Article();
        if(!isset($array)){
            $array = $model->getCategoryList();
        }
        $itemsArr = [];
        $category = isset(Yii::$app->request->queryParams['category']) ? Yii::$app->request->queryParams['category'] : null;
        if($type=='li') {
            foreach ($array as $db => $text) {
                $liOptions = $options;
                if($text == $category){
                   if(isset($linkOptions['class'])) {
                       $liOptions['class'] = $liOptions['class'] . ' active';
                   } else {
                       $liOptions['class'] = 'active';
                   }
                }
                $itemsArr[] = Html::tag('li', Html::a($text, [self::CATEGORY_URL . $text], $linkOptions), $liOptions);
            }
        }elseif($type=='menu'){
            foreach($array as $db => $text){
                $itemsArr[] = [ 'label' => $text, 'url' => [self::CATEGORY_URL . $text] ];
            }

        }

        return $itemsArr;
    }
}
