/**
* Common js function.
*/
function enC(str)
{
	return encodeURIComponent(str);
}
function deC(str)
{
	return decodeURIComponent(str);
}

function goToLogin()
{
	document.location.href='/login/login.php';
}

function goToIndex()
{
	document.location.href='/index.html?'+Math.random();
}

function validInt(sDouble)
{
  var re = /^[0-9]+.?[0-9]*$/;
   var check = sDouble.indexOf('0');
  if(check == 0)
  {
	  return false;
	}
  return re.test(sDouble)
}
