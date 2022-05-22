<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class SupplierSearch extends Supplier
{
    public function rules()
    {
        // 只有在 rules() 函数中声明的字段才可以搜索
        return [
            [['id'], 'string'],
            [['name'], 'string'],
            [['code'], 'string'],
            [['t_status'], 'in', 'range' => ['ok','hold']],
        ];
    }

    public function scenarios()
    {
        // 旁路在父类中实现的 scenarios() 函数
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Supplier::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 7,
            ],
        ]);

        if (isset($params['ids'])) {
            $ids = explode(',',$params['ids']);
            $query->where(['in', 'id', $ids]);
        }
        else{
            // 从参数的数据中加载过滤条件，并验证
            if (!($this->load($params) && $this->validate())) {
                return $dataProvider;
            }

            // 增加过滤条件来调整查询对象
            $query->andFilterCompare('id', $this->id);
            $query->andFilterWhere(['like', 'name', $this->name])
                  ->andFilterWhere(['like', 'code', $this->code])
                  ->andFilterWhere(['t_status' => $this->t_status]);
        }

        return $dataProvider;
    }
}