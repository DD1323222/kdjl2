<?php
set_time_limit(300);
ignore_user_abort(true);//用户关闭浏览器不退出
require_once('./config/config.game.php');

function getRandomString($length = 10)
{
    // 从ASCII中获取
    $code = '';

    // 取随机数
    for ($i = 0; $i < $length; $i++) {
        switch (mt_rand(1, 3)) {
            case 1:                 // 数字：49-57表示数字1-9
                $code .= chr(mt_rand(49, 57));
                break;
            case 2:                 // 小写字母 a-z
                $code .= chr(mt_rand(65, 90));
                break;
            case 3:                 // 大写字母 A-Z
                $code .= chr(mt_rand(97, 122));
                break;
            default:
                $code .= 'x';
                break;
        }
    }
    return $code;
}

$reg_code = '生成的注册码在这里';

if (isset($_REQUEST['operate_code']) && $_REQUEST['operate_code'] == 'wa8MiX2V0r') {
    $new_reg_code = getRandomString();
    $sql = "SELECT * from reg_code where reg_code = '{$new_reg_code}'";
    $tm = $_pm["mysql"]->getOneRecord($sql);
    if (!is_array($tm)) {
        $sql = "INSERT into reg_code (reg_code, total_count, used_count) values ('{$new_reg_code}', 2, 0)";
        $_pm["mysql"]->query($sql);
        $reg_code = $new_reg_code;
    } else{
        $reg_code = "出了点小问题，请重新生成注册码！";
    }
}


if (stripos($_SERVER['PHP_SELF'], 'reg_code') !== false) {

    ?>

    <style type="text/css">
        <!--
        body, td, th {
            font-size: 12px;
        }

        body {
            margin-left: 0px;
            margin-top: 0px;
            margin-right: 0px;
            margin-bottom: 0px;
        }

        table .red {
            color: #FF0000
        }

        .STYLE1 {
            color: #FF0000
        }

        -->
    </style>
    <center>
        <form id="form1" name="form1" method="get" action="">
            <table width="778" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="72" height="25" align="right">生成注册码：</td>
                    <td width="706" height="25">
                        <p>
                            <input name="operate_code" id="operate_code" placeholder="请填写后台码"/>
                            <input type="submit" name="Submit" value="生成注册码"/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td width="72" height="25" align="right"></td>
                </tr>
                <tr>
                    <td width="72" height="25" align="right">注册码：</td>
                    <td class="red"><?php echo $reg_code; ?></td>
                </tr>
            </table>
        </form>
    </center>
    <?php
} ?>

