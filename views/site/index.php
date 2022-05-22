<?php
use yii\grid\GridView;
use yii\bootstrap4\Button;
use yii\bootstrap4\Html;
use yii\web\View;
use yii\helpers\URL;

/** @var yii\web\View $this */

$this->title = 'My Yii Application';

?>
<div class="site-index">
    <div class="body-content">

        <div class="row">
            <div class="col-lg">
                <?php
                    echo Button::widget([
                        'label' => 'Export',
                        'options' => [
                            'class' => 'btn-primary',
                            'id' => 'export-button',
                            'data-url' => URL::toRoute('site/export'),
                        ],
                    ]);
                ?>
                <?php
                    echo GridView::widget([
                        'id' => 'supplier-gridview',
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            ['class' => yii\grid\CheckboxColumn::class],
                            'id',
                            'name',
                            'code',
                            ['attribute' => 't_status', 'filter' => ['ok'=>'ok','hold'=>'hold']],
                        ],
                    ]);
                ?>
            </div>
        </div>
        <div class="partial-selection" style="display:none;">
            <span class="text-bold">All</span> <span class="filtered-amount">0</span> suppliers on this page have been selected.
            <a href="#" class="select-all">Select all suppliers that match this search</a>
        </div>
        <div class="full-selection" style="display: none;">
            All suppliers in this search have been selected.
            <a href="#" class="clear-selection">Clear selection.</a>
        </div>
    </div>
</div>

<?php

 $this->registerJs(
        <<<EOF
            let selection_key = 'selection_ids';
            let conditions_key = 'conditions';
            let full_selection_key = 'full_selection';


            function getQueryVariable(variable) {
                var query = window.location.search.substring(1);
                var vars = query.split('&');
                for (var i = 0; i < vars.length; i++) {
                    var pair = vars[i].split('=');
                    if (decodeURIComponent(pair[0]) == variable) {
                        return decodeURIComponent(pair[1]);
                    }
                }
                // console.log('Query variable %s not found', variable);
            }

            function getFromLocalStorageAndDecode(key){
                var item = localStorage.getItem(key);
                if(!item){
                    return {};
                }
                return JSON.parse(item);
            }
            function setToLocalStorageAndEncode(key, value){
                localStorage.setItem(key, JSON.stringify(value));

            }

            function getIds(){
                return getFromLocalStorageAndDecode(selection_key);
            }
            function setIds(ids){
                setToLocalStorageAndEncode(selection_key, ids);
            }
            function addId(id){
                var selectedIds = getIds();
                selectedIds[id] = true;
                setIds(selectedIds);
            }
            function delId(id){
                var selectedIds = getIds();
                if (id in selectedIds) {
                    delete selectedIds[id];
                }
                setIds(selectedIds);
            }
            function delAll(){
                setIds({});
            }

            function getConditions(){
                return getFromLocalStorageAndDecode(conditions_key);
            }
            function getCondition(key){
                var conds = getConditions();
                if(key in conds){
                    return conds[key];
                }
                return undefined;
            }
            function setConditions(conditions){
                return setToLocalStorageAndEncode(conditions_key, conditions);
            }
            function setCondition(key, value){
                var conditions = getConditions();
                conditions[key] = value;
                setConditions(conditions);
            }

            function setFullSelection(enable){
                setToLocalStorageAndEncode(full_selection_key, enable);
                if(enable){
                    $('.partial-selection').hide();
                    //request all ids
                    $('.full-selection').show();
                }
                else{
                    // $('.partial-selection').show();
                    $('.full-selection').hide();
                }
            }
            function getFullSelection(enable){
                return getFromLocalStorageAndDecode(full_selection_key) || false;
            }

            //计算当前显示条数
            var amount = $('input[name="selection[]"]').length;
            $('.filtered-amount').text(amount);


            //记录当前的搜索条件
            $('.filters .form-control').on('change', function(){
                setCondition($(this).attr('name'), $(this).val());
            });

            let sameCondition = true;
            $('.filters .form-control').each(function(key, item){
                var name = $(item).attr('name');
                var newValue = getQueryVariable(name);
                var oldValue = getCondition(name);
                if (newValue != oldValue) {
                    setFullSelection(false);
                    sameCondition = false;
                }
                setCondition(name, newValue);
            });
            //如果搜索条件一样且跨页选中，则默认勾选
            if (sameCondition && getFullSelection()){
                $('input[name="selection[]"]').each(function(key, item){
                    addId($(item).val());
                });
                //让跨页选中的提示显示
                setFullSelection(true)
            }

            //判断当前页是否全部选中
            var selectedIds = getIds();
            //默认勾选
            $.each(selectedIds, function(key,item){
                $('input[name="selection[]"][value="'+key+'"]').attr('checked', 'checked');
            });

            var notSelectedInputAmount = $('input[name="selection[]"]:not(:checked)').length;
            if (notSelectedInputAmount == 0 ){
                $('.select-on-check-all').attr('checked', 'checked');
            }


            $('#export-button').on('click', function(){
                var queryString = location.search;
                //如果是跨页选中，那直接按搜索条件导出即可
                //如果有选中id，那么只导出选中的id
                //如果没有选中id，那么等同于导出全部符合条件的结果。效果等同于跨页选中
                var queryString = '';
                var ids = [];
                var url = $(this).data('url');
                if (!getFullSelection()){
                    $('input[name="selection[]"]:checked').each(function(key, item){
                        ids.push($(item).val().toString());
                    });
                    queryString = 'ids='+ids.join(',');
                }
                location.href = decodeURIComponent(url)+'&'+queryString;
            });

            $('input[name="selection[]"]').on('click', function(){
                var id = $(this).val();
                toggleId(id, $(this).prop('checked'))
            });

            $('.select-on-check-all').on('click', function(){
                var selected = $(this).prop('checked');
                if (selected) {
                    $('.partial-selection').show();
                }
                else{
                    $('.partial-selection').hide();
                }
                $('input[name="selection[]"]').each(function(key, item){
                    var id = $(item).val(); 
                    toggleId(id, selected)
                });
            });

            $('.select-all').on('click', function(e){
                e.preventDefault();
                setFullSelection(true);
            });

            $('.clear-selection').on('click', function(e){
                e.preventDefault();
                delAll();
                $('.select-on-check-all').prop('checked', false);
                $('input[name="selection[]"]:checked').click();
                setFullSelection(false)
            })

            function toggleId(id,checked){
                if(checked){
                    addId(id);
                }
                else{
                    delId(id);
                }
            }
        EOF,
        View::POS_READY,
        'export-handler'
    );
?>