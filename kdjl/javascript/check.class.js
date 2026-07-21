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
	return /^[1-9][0-9]*$/.test(String(sDouble));
}
