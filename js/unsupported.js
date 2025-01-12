/*-----Access Check-----*/

const ua = window.navigator.userAgent;
/*browser*/
if (ua.indexOf('Edge') != -1 || ua.indexOf('Edg') != -1) {
    user_agent_browser = 'Microsoft_Edge';
} else if (ua.indexOf('Trident') != -1 || ua.indexOf('MSIE') != -1) {
    user_agent_browser = 'Microsoft_Internet_Explorer';
} else if (ua.indexOf('OPR') != -1 || ua.indexOf('Opera') != -1) {
    user_agent_browser = 'Opera';
} else if (ua.indexOf('Chrome') != -1) {
    user_agent_browser = 'Google_Chrome';
} else if (ua.indexOf('Firefox') != -1) {
    user_agent_browser = 'FireFox';
} else if (ua.indexOf('Safari') != -1) {
    user_agent_browser = 'Safari';
} else if (ua.indexOf('NintendoBrowser') != -1) {
    user_agent_browser = 'NintendoBrowser';
} else {
    user_agent_browser = 'Other';
}

/*OS*/
const ua2 = ua.toLowerCase();
if (ua2.indexOf("windows nt") !== -1) {
    user_agent_os = "Microsoft_Windows_NT";
} else if (ua.indexOf("Android") !== -1) {
    user_agent_os = "Android";
} else if (ua.indexOf("iPhone") !== -1) {
    ua.match(/iPhone OS (\w+){1,4}/g);
    var iosv = (RegExp.$1.replace(/_/g, '.')).slice(0, 4);
    if (iosv >= 6.0) {
        user_agent_os = "iOS_6_Over";
    } else {
        user_agent_os = "iOS_6_Under";
    }
} else if (ua.match(/Linux/)) {
    user_agent_os = "Linux";
} else if (ua.indexOf("ipad") !== -1 || ua.indexOf("Mac OS X") !== -1 && typeof document.ontouchstart !== 'undefined') {
    user_agent_os = "iPad";
} else if (ua.indexOf("Mac OS X") !== -1) {
    user_agent_os = "mac_OS";
} else if (ua.match(/^.*\s([A-Za-z]+BSD)/)) {
    user_agent_os = RegExp.$1;
} else if (ua.match(/SunOS/)) {
    user_agent_os = "Solaris";
} else if (ua.match("Nintendo Wii")) {
    user_agent_os = "Nintendo_Wii";
} else if (ua.match("PlayStation 4")) {
    user_agent_os = "SONY_PS4";
} else if (ua.match("PlayStation 5")) {
    user_agent_os = "SONY_PS5";
} else if (ua.match("PlayStation Vita")) {
    user_agent_os = "SONY_PSVita";
} else if (ua.match("Nintendo Switch")) {
    user_agent_os = "Nintendo_Switch";
} else if (ua.match("Windows Phone")) {
    user_agent_os = "Windows_Phone";
} else {
    user_agent_os = 'Other';
}

/*SSL*/
if (location.protocol == 'http:') {
    if (location.hostname == 'localhost') {
        user_agent_ssl = "not_ssl";
    } else {
        user_agent_ssl = "not_ssl_bad";
    }
} else if (location.protocol == 'https:') {
    user_agent_ssl = "ssl";
} else {
    user_agent_ssl = "Other";
}

/*Cookie*/
if (navigator.cookieEnabled) {
    user_agent_cookie = 'cookie_on';
} else {
    user_agent_cookie = 'cookie_off';
}

/*Main Access check*/
if (user_agent_browser == 'Microsoft_Internet_Explorer' || user_agent_browser == 'NintendoBrowser') {
    user_agent_access = 'bad';
    errcode = 'UNSUPPORTED_BROWSER';
} else if (user_agent_os == 'Nintendo_Wii' || user_agent_os == 'SONY_PSVita' || user_agent_os == 'Nintendo_Switch' || user_agent_os == 'Windows_Phone' || user_agent_os == 'iOS_6_Under') {
    user_agent_access = 'bad';
    errcode = 'UNSUPPORTED_OS';
} else if (user_agent_cookie == 'cookie_off') {
    user_agent_access = 'bad';
    errcode = 'PLEASE_COOKIE_ON';
} else if (user_agent_ssl == 'Other') {
    user_agent_access = 'bad';
    errcode = 'NONE_SSL';
} else if (user_agent_ssl == 'not_ssl_bad') {
    user_agent_access = 'bad';
    errcode = 'NONE_SSL_SERVER';
} else {
    user_agent_access = 'ok';
    errcode = 'NONE_ERROR';
}

/*
console.log('browser : '+user_agent_browser);
console.log('cookie : '+user_agent_cookie);
console.log('os : '+user_agent_os);
console.log('ssl : '+user_agent_ssl);
console.log('access : '+user_agent_access);
console.log('errorcode : '+errcode);
*/

if (user_agent_access == 'bad') {
    setTimeout(link(), 0);
    function link() {
        location.href = "../unsupported.php?errcode=" + errcode + "&browser=" + user_agent_browser + "&os=" + user_agent_os + "&cookie=" + user_agent_cookie + "&ssl=" + user_agent_ssl + "&block=null"
    }
}

