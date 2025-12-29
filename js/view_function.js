//------------------------------------------------ユーズ表示系関数--------------------------------------------------
var global_userid;
var account_id;

function view_ueuse_init(user_id, loginid) {
    global_userid = user_id;
    global_account_id = loginid;
    return true;
}

const mentionCache = {};
const fetchingMentions = false;

async function replaceMentions(text) {
    const placeholders = [];
    let index = 0;

    // aタグの一時置き換え
    text = text.replace(/<a\b[^>]*>.*?<\/a>/gi, (match) => {
        const placeholder = `\u2063{{PLACEHOLDER${index}}}\u2063`;
        placeholders.push(match);
        index++;
        return placeholder;
    });

    const mentionRegex = /@([a-zA-Z0-9_]+)(?:@([a-zA-Z0-9_.-]+))?/g;
    const mentionMatches = [...text.matchAll(mentionRegex)];

    if (mentionMatches.length === 0) {
        placeholders.forEach((original, i) => {
            text = text.replace(`\u2063{{PLACEHOLDER${i}}}\u2063`, original);
        });
        return text;
    }

    const localMentionsToFetch = [];
    const localMentionSet = new Set();

    for (const match of mentionMatches) {
        const userid = match[1];
        const domain = match[2];

        if (domain) {
            const cacheKey = `${userid}@${domain}`;
            if (!mentionCache[cacheKey]) {
                mentionCache[cacheKey] = `<a href="/@${userid}@${domain}" class="mta">@${userid}@${domain}</a>`;
            }
        } else {
            if (!mentionCache[userid] && !localMentionSet.has(userid)) {
                localMentionsToFetch.push(userid);
                localMentionSet.add(userid);
            }
        }
    }

    if (localMentionsToFetch.length > 0) {
        await new Promise((resolve) => {
            $.ajax({
                url: '../function/get_userid.php',
                method: 'POST',
                data: {
                    get_account: localMentionsToFetch.join(','),
                    userid: global_userid,
                    account_id: global_account_id
                },
                dataType: 'json',
                timeout: 300000,
                success: function (response) {
                    if (response.success && response.users) {
                        for (const [name, userInfo] of Object.entries(response.users)) {
                            if (userInfo && userInfo.userid && userInfo.username) {
                                mentionCache[name] = `<a href="/@${userInfo.userid}" class="mta">@${userInfo.username}</a>`;
                            } else {
                                mentionCache[name] = `@${name}`;
                            }
                        }
                    }
                    resolve();
                },
                error: function () {
                    for (const name of localMentionsToFetch) {
                        mentionCache[name] = `@${name}`;
                    }
                    resolve();
                }
            });
        });
    }

    text = text.replace(mentionRegex, (match, userid, domain) => {
        if (domain) {
            const cacheKey = `${userid}@${domain}`;
            return mentionCache[cacheKey] || match; 
        } else {
            return mentionCache[userid] || match;
        }
    });

    // aタグ戻す
    placeholders.forEach((original, i) => {
        text = text.replace(`\u2063{{PLACEHOLDER${i}}}\u2063`, original);
    });

    return text;
}

const CACHE_KEY = 'emojiCache';
const CACHE_EXPIRY_KEY = 'emojiCacheExpiry';
const CACHE_LIFETIME_MS = 24 * 60 * 60 * 1000; // 24時間

let emojiCache = {};
let fetchingEmojis = {};

(function loadEmojiCache() {
    const savedCache = localStorage.getItem(CACHE_KEY);
    const savedExpiry = localStorage.getItem(CACHE_EXPIRY_KEY);

    if (savedCache && savedExpiry && Date.now() < Number(savedExpiry)) {
        try {
            emojiCache = JSON.parse(savedCache);
        } catch (e) {
            localStorage.removeItem(CACHE_KEY);
            localStorage.removeItem(CACHE_EXPIRY_KEY);
        }
    } else {
        localStorage.removeItem(CACHE_KEY);
        localStorage.removeItem(CACHE_EXPIRY_KEY);
    }
})();

function saveEmojiCache() {
    localStorage.setItem(CACHE_KEY, JSON.stringify(emojiCache));
    localStorage.setItem(CACHE_EXPIRY_KEY, (Date.now() + CACHE_LIFETIME_MS).toString());
}

async function replaceCustomEmojis(text) {
    const emojiMatches = [...text.matchAll(/:([a-zA-Z0-9_]+):/g)];
    if (emojiMatches.length === 0) return text;

    const uniqueEmojis = [...new Set(emojiMatches.map(match => match[1]))];
    const emojisToFetch = uniqueEmojis.filter(name => !emojiCache[name] && !fetchingEmojis[name]);

    if (emojisToFetch.length > 0) {
        const fetchPromise = new Promise((resolve) => {
            $.ajax({
                url: '../function/get_customemoji.php',
                method: 'POST',
                data: {
                    emoji: emojisToFetch.join(','),
                    userid: global_userid,
                    account_id: global_account_id
                },
                dataType: 'json',
                timeout: 30000,
                success: function (response) {
                    if (response.success && response.emojis) {
                        for (const name of emojisToFetch) {
                            if (response.success && response.emojis) {
                                for (const name of emojisToFetch) {
                                    if (response.emojis[name]) {
                                        const emoji = response.emojis[name];
                                        emojiCache[name] = emoji.emojipath;
                                    } else {
                                        emojiCache[name] = null;
                                    }
                                }
                            }
                        }
                    } else {
                        for (const name of emojisToFetch) {
                            emojiCache[name] = null;
                        }
                    }
                    saveEmojiCache();
                    resolve();
                },
                error: function () {
                    for (const name of emojisToFetch) {
                        emojiCache[name] = null;
                    }
                    saveEmojiCache();
                    resolve();
                }
            });
        });

        emojisToFetch.forEach(name => {
            fetchingEmojis[name] = fetchPromise;
        });

        await fetchPromise;
    }

    await Promise.all(uniqueEmojis.map(name => fetchingEmojis[name]));

    text = text.replace(/:([a-zA-Z0-9_]+):/g, (_, name) => {
        const url = emojiCache[name];
        if (url === undefined) return `:${name}:`; // 未取得
        if (url === null) return `:${name}:`;       // 存在しない

        return `<img src="${url}" alt=":${name}:" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/emoji_404.png'">`; // ここで生成
    });

    return text;
}

function saveLocalstorage(key, value, pagepath){
    try {
        const valueToSave = value;
        const now = new Date();
        
        const expirationTime = now.getTime() + (180 * 24 * 60 * 60 * 1000);

        const dataToStore = {
            value: valueToSave,
            expiresAt: expirationTime
        };

        if(pagepath === true){
            key = key + "_" + location.pathname;
        }

        localStorage.setItem(key, JSON.stringify(dataToStore));
        return true;
    } catch (e) {
        return false;
    }
}

function getLocalstorage(key, pagepath) {
    try {
        if(pagepath === true){
            key = key + "_" + location.pathname;
        }

        const storedDataString = localStorage.getItem(key);
        if (!storedDataString) {
            return null; // データが存在しない
        }

        const storedData = JSON.parse(storedDataString);
        const now = new Date().getTime();

        if (now > storedData.expiresAt) {
            localStorage.removeItem(key);
            return null;
        }

        return storedData.value;
    } catch (e) {
        return null;
    }
}

function deleteLocalstorage(key, pagepath) {
    try {
        if(pagepath === true){
            key = key + "_" + location.pathname;
        }
        
        localStorage.removeItem(key);
        return true;
    } catch (e) {
        return false;
    }
}

/*
function a_link(text) {
    const placeholders = {};
    let placeholderIndex = 0;

    text = text.replace(/&#039;/g, (match) => {
        const key = `\u2063{{PLACEHOLDER${placeholderIndex++}}}\u2063`;
        placeholders[key] = match; // 元の文字列を保存
        return key;
    });

    text = text.replace(/(https:\/\/[\w!?\/+\-_~;.,*&@#$%()+|https:\/\/[ぁ-んァ-ヶ一ー-龠々\w\-\/?=&%.]+)/g, function (url) {
        const escapedUrl = url;
        const no_https_link = escapedUrl.replace("https://", "");
        if (no_https_link.length > 48) {
            const truncatedLink = no_https_link.substring(0, 48) + '...';
            return `<a href="${escapedUrl}" target="_blank" rel="noopener">${truncatedLink}</a>`;
        } else {
            return `<a href="${escapedUrl}" target="_blank" rel="noopener">${no_https_link}</a>`;
        }
    });

    text = text.replace(/(^|[^a-zA-Z0-9_])#([a-zA-Z0-9ぁ-んァ-ン一-龥ー_]+)/gu, function (match, before, tag) {
        const encodedTag = encodeURIComponent("#" + tag);
        return `${before}<a href="/search?q=${encodedTag}" class="hashtags">#${tag}</a>`;
    });

    for (const key in placeholders) {
        const escapedKey = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        text = text.replace(new RegExp(escapedKey, 'g'), placeholders[key]);
    }

    return text;
}
*/

function formatMarkdown(text) {
    const placeholders = {};
    let placeholderIndex = 0;

    // ヘルパー関数: プレースホルダーを作成して保存
    function createPlaceholder(content) {
        const key = `\u2063{{PLACEHOLDER${placeholderIndex++}}}\u2063`;
        placeholders[key] = content;
        return key;
    }

    text = text.replaceAll('&#039;', "'");

    // 複数行コードブロック (```)
    text = text.replace(/```([\s\S]+?)```/g, (match, code) => {
        // 先頭の改行のみ削除
        const cleanCode = code.replace(/^\s*\n/, '');
        // <pre><code> で包んで退避
        return createPlaceholder(`<pre class="codeblock"><code>${cleanCode}</code></pre>`);
    });

    // インラインコード (`)
    text = text.replace(/`([^`\n]+)`/g, (_, code) => {
        return createPlaceholder(`<span class="inline">${code}</span>`);
    });

    // 既存のaタグ
    text = text.replace(/<a\b[^>]*>[\s\S]*?<\/a>/gi, (match) => {
        return createPlaceholder(match);
    });

    // ユーザーメンション (@user)
    text = text.replace(/@([a-zA-Z0-9_]+)(?:@([a-zA-Z0-9_.-]+))?/g, (match) => {
        return createPlaceholder(match);
    });
    
    // カスタム絵文字 (:emoji:)
    text = text.replace(/:([a-zA-Z0-9_]+):/g, (match) => {
        return createPlaceholder(match);
    });

    // a_link
    text = text.replace(/(https:\/\/[\w!?\/+\-_~;.,*&@#$%()+|https:\/\/[ぁ-んァ-ヶ一ー-龠々\w\-\/?=&%.]+)/g, function (url) {
        const escapedUrl = url;
        const no_https_link = escapedUrl.replace("https://", "");
        let linkText = no_https_link;
        
        if (no_https_link.length > 48) {
            linkText = no_https_link.substring(0, 48) + '...';
        }
        
        return `<a href="${escapedUrl}" target="_blank" rel="noopener">${linkText}</a>`;
    });

    // ハッシュタグ
    text = text.replace(/(^|[^a-zA-Z0-9_])#([a-zA-Z0-9ぁ-んァ-ン一-龥ー_]+)/gu, function (match, before, tag) {
        const encodedTag = encodeURIComponent("#" + tag);
        return `${before}<a href="/search?q=${encodedTag}" class="hashtags">#${tag}</a>`;
    });

    // 独自構文
    text = text.replace(/\[\[buruburu (.+?)\]\]/g, '<span class="buruburu">$1</span>');
    text = text.replace(/\[\[time (\d+)\]\]/g, (_, ts) => {
        const d = new Date(parseInt(ts, 10) * 1000);
        return `<span class="unixtime" title="${d.toLocaleString()}">${d.toLocaleString()}</span>`;
    });

    // 文字装飾
    text = text
        .replace(/\*\*\*(.+?)\*\*\*/g, '<b><i>$1</i></b>')
        .replace(/___(.+?)___/g, '<b><i>$1</i></b>')
        .replace(/\*\*(.+?)\*\*/g, '<b>$1</b>')
        .replace(/__(.+?)__/g, '<b>$1</b>')
        .replace(/\*(.+?)\*/g, '<i>$1</i>')
        .replace(/_(.+?)_/g, '<i>$1</i>')
        .replace(/~~(.+?)~~/g, '<s>$1</s>')
        .replace(/^&gt;&gt;&gt; ?(.*)$/gm, '<span class="quote">$1</span>')
        .replace(/\|\|(.+?)\|\|/g, '<span class="blur">$1</span>')
        .replace(/^# (.+)/gm, '<h1>$1</h1>')
        .replace(/^## (.+)/gm, '<h2>$1</h2>')
        .replace(/^### (.+)/gm, '<h3>$1</h3>')
        .replace(/^- (.+)/gm, '・ $1');

    // 行ごとに <p> タグで囲む
    const lines = text.split('\n').map(line => {
        line = line.trim();
        return line === '' ? '<br>' : `<p>${line}</p>`;
    });
    
    let final = lines.join('');

    for (const key in placeholders) {
        // キー自体に正規表現の特殊文字が含まれるためエスケープ
        const escapedKey = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        final = final.replace(new RegExp(escapedKey, 'g'), placeholders[key]);
    }

    return final;
}

function YouTube_and_nicovideo_Links(postText) {
    const urlPattern = /(https:\/\/[^\s<>\[\]'"“”]+)/g;
    const urls = postText.match(urlPattern);
    let embedCode = '';

    if (!urls) return null;

    let embeddedOnce = false; // ← 埋め込みが1回されたかどうか

    urls.forEach(url => {
        if (embeddedOnce) return; // ← すでに埋め込みしたらスキップ

        try {
            const parsed = new URL(url);
            const host = parsed.hostname.replace(/^www\./, '');
            let videoId = '';
            let videoTime = '0';
            let iframe = false;

            if (['youtube.com', 'youtu.be', 'm.youtube.com'].includes(host)) {
                if (parsed.hostname === 'youtu.be') {
                    videoId = parsed.pathname.replace('/', '');
                    iframe = true;
                } else if (parsed.searchParams.has('v')) {
                    videoId = parsed.searchParams.get('v');
                    iframe = true;
                } else if (parsed.pathname.startsWith('/shorts/')) {
                    videoId = parsed.pathname.replace('/shorts/', '');
                    iframe = true;
                }

                if (parsed.searchParams.has('t') || parsed.searchParams.has('start')) {
                    videoTime = parsed.searchParams.get('t') || parsed.searchParams.get('start') || '0';
                    if (isNaN(parseInt(videoTime))) videoTime = '0';
                }

                if (iframe && videoId) {
                    embedCode = `<div class="youtube_and_nicovideo_player"><iframe src="https://www.youtube-nocookie.com/embed/${videoId}?start=${videoTime}" rel="0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>`;
                    embeddedOnce = true;
                }

            } else if (['nicovideo.jp', 'nico.ms'].includes(host)) {
                if (parsed.pathname.includes('/watch/')) {
                    videoId = parsed.pathname.split('/watch/')[1];
                    iframe = true;
                } else {
                    videoId = parsed.pathname.replace('/', '');
                    iframe = true;
                }

                if (parsed.searchParams.has('from')) {
                    videoTime = parsed.searchParams.get('from');
                    if (isNaN(parseInt(videoTime))) videoTime = '0';
                }

                if (iframe && videoId) {
                    embedCode = `<div class="youtube_and_nicovideo_player"><iframe src="https://embed.nicovideo.jp/watch/${videoId}?from=${videoTime}" frameborder="0" allowfullscreen></iframe></div>`;
                    embeddedOnce = true;
                }
            } else {
                embedCode = null
            }
        } catch (e) {
            // 無視
        }
    });

    return embedCode;
}


function formatSmartDate(datetimeStr) {
    const date = new Date(datetimeStr.replace(" ", "T"));
    const now = new Date();
    const diffMs = now - date;
    const diffAbs = Math.abs(diffMs);
    const future = diffMs < 0;

    const pad = (n) => n.toString().padStart(2, '0');
    const hhmm = `${pad(date.getHours())}:${pad(date.getMinutes())}`;

    const y = date.getFullYear();
    const m = date.getMonth();
    const d = date.getDate();

    const nowY = now.getFullYear();
    const nowM = now.getMonth();
    const nowD = now.getDate();

    const dayDiff = Math.floor((new Date(y, m, d) - new Date(nowY, nowM, nowD)) / (1000 * 60 * 60 * 24));

    if (!future && diffAbs < 30 * 1000) return "今";
    if (future && diffAbs < 60 * 1000) return "まもなく";
    if (future && diffAbs < 60 * 60 * 1000) return `${Math.floor(diffAbs / 1000 / 60)}分後`;

    if (dayDiff === 0) return `今日 ${hhmm}`;
    if (dayDiff === 1) return `明日 ${hhmm}`;

    if (!future && y === nowY && m === 0 && d === 1) return `元日 ${hhmm}`;

    if (y === nowY) return `${pad(m + 1)}/${pad(d)} ${hhmm}`;

    return `${y}/${pad(m + 1)}/${pad(d)} ${hhmm}`;
}

function getCheckIcon(userdata) {
    if (userdata["role"] && userdata["role"].includes("official")) {
        return `<div class="checkicon"><div class="check"></div></div>`;
    }
    return "";
}

function getBotIcon(userdata) {
    if (userdata["is_bot"] && userdata["is_bot"] == true) {
        return `<div class="bot">Bot</div>`;
    }
    return "";
}

function getAIBlockFlag(userdata) {
    if (userdata["is_aiblock"] && userdata["is_aiblock"] == true) {
        return `data-aiblock = "true"`;
    }else{
        return `data-aiblock = "false"`;
    }
}

async function createUeuseHtml(ueuse, selectedUniqid = null) {
    let html = "";
    let check = "";
    let bot = "";
    let AIBlock = "";
    var reuse = "";
    let contentHtml = "";

    var uniqid = "";
    var userid = "";
    var username = "";
    var iconurl = "";
    var datetime = "";
    var favoritecount = 0;
    var replycount = 0;
    var reusecount = 0;
    var is_favorite = false;
    var is_bookmark = false;
    var is_nsfw = false;
    var is_reuse_getted = false;
    var abi = "";
    var abi_date = "";
    var abi_html = "";
    var addabi = "";
    var inyo = "";
    var img1 = "";
    var img2 = "";
    var img3 = "";
    var img4 = "";
    var vid1 = "";
    var img_html = "";
    var vid_html = "";
    var nsfw_html = "";
    var nsfw_start_html = "";
    var nsfw_end_html = "";

    if (ueuse["type"] == "Reuse") {
        if (ueuse["reuse"]) {
            check = getCheckIcon(ueuse["userdata"]);
            bot = getBotIcon(ueuse["userdata"]);
            AIBlock = getAIBlockFlag(ueuse["userdata"]);
        }

        if (ueuse["ueuse"].length > 0) {
            reuse = ``;
            if (!(ueuse["reuse"] == null)) {
                // カスタム絵文字を非同期に差し替え
                var inyoreuseHtml = formatMarkdown(ueuse["reuse"]["ueuse"]);
                inyoreuseHtml = await replaceMentions(inyoreuseHtml);
                inyoreuseHtml = await replaceCustomEmojis(inyoreuseHtml);

                inyo = `<div class="reuse_box" data-uniqid="` + ueuse["reuse"]["uniqid"] + `" id="quote_reuse">
                            <div class="reuse_flebox">
                                <a href="/!`+ ueuse["reuse"]["uniqid"] + `">
                                    <img src="`+ ueuse["reuse"]["userdata"]["iconurl"] + `" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/icon_404.png'">
                                </a>
                                <a href="/!`+ ueuse["reuse"]["uniqid"] + `">
                                    <div class="u_name">
                                        `+ await replaceCustomEmojis(ueuse["reuse"]["userdata"]["username"]) + `
                                    </div>
                                </a>
                                <div class="idbox">
                                    <a href="/@`+ ueuse["reuse"]["userdata"]["userid"] + `">
                                        @`+ ueuse["reuse"]["userdata"]["userid"] + `
                                    </a>
                                </div>
                            </div>

                            <p>
                                `+ inyoreuseHtml + `
                            </p>
                        </div>`;
            } else {
                inyo = `<div class="reuse_box" id="quote_reuse">
                            <p>
                                リユーズ元のユーズは削除されました。
                            </p>
                        </div>`;
            }

            contentHtml = formatMarkdown(ueuse["ueuse"]);

            uniqid = ueuse["uniqid"];
            userid = ueuse["userdata"]["userid"];
            username = ueuse["userdata"]["username"];
            iconurl = ueuse["userdata"]["iconurl"];
            datetime = ueuse["datetime"];
            favoritecount = ueuse["favoritecount"];
            replycount = ueuse["replycount"];
            reusecount = ueuse["reusecount"];
            is_favorite = ueuse["is_favorite"];
            is_bookmark = ueuse["is_bookmark"];

            is_nsfw = ueuse["nsfw"];

            img1 = ueuse["photo1"];
            img2 = ueuse["photo2"];
            img3 = ueuse["photo3"];
            img4 = ueuse["photo4"];
            vid1 = ueuse["video1"];

            abi = ueuse["abi"]["abi_text"];
            abi_date = ueuse["abi"]["abi_date"];
        } else {
            if (!(ueuse["reuse"] == null)) {
                check = getCheckIcon(ueuse["reuse"]["userdata"]);
                bot = getBotIcon(ueuse["reuse"]["userdata"]);
                AIBlock = getAIBlockFlag(ueuse["reuse"]["userdata"]);
                reuse = `<div class="ru">
                            <a href="/@`+ ueuse["userdata"]["userid"] + `">
                                <img src="`+ ueuse["userdata"]["iconurl"] + `" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/icon_404.png'">
                                <p>`+ await replaceCustomEmojis(ueuse["userdata"]["username"]) + `さんがリユーズ</p>
                            </a>
                        </div>`;
                inyo = ``;
                contentHtml = formatMarkdown(ueuse["reuse"]["ueuse"]);

                uniqid = ueuse["reuse"]["uniqid"];
                userid = ueuse["reuse"]["userdata"]["userid"];
                username = ueuse["reuse"]["userdata"]["username"];
                iconurl = ueuse["reuse"]["userdata"]["iconurl"];
                datetime = ueuse["reuse"]["datetime"];
                favoritecount = ueuse["reuse"]["favoritecount"];
                replycount = ueuse["reuse"]["replycount"];
                reusecount = ueuse["reuse"]["reusecount"];
                is_favorite = ueuse["reuse"]["is_favorite"];
                is_bookmark = ueuse["reuse"]["is_bookmark"];

                is_nsfw = ueuse["reuse"]["nsfw"];

                img1 = ueuse["reuse"]["photo1"];
                img2 = ueuse["reuse"]["photo2"];
                img3 = ueuse["reuse"]["photo3"];
                img4 = ueuse["reuse"]["photo4"];
                vid1 = ueuse["reuse"]["video1"];

                abi = ueuse["reuse"]["abi"]["abi_text"];
                abi_date = ueuse["reuse"]["abi"]["abi_date"];
            } else {
                reuse = `<div class="ru">
                            <a href="/@`+ ueuse["userdata"]["userid"] + `">
                                <img src="`+ ueuse["userdata"]["iconurl"] + `" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/icon_404.png'">
                                <p>`+ await replaceCustomEmojis(ueuse["userdata"]["username"]) + `さんがリユーズ</p>
                            </a>
                        </div>`;
                inyo = `<div class="reuse_box" id="quote_reuse">
                            <p>
                                リユーズ元のユーズは削除されました。
                            </p>
                        </div>`;
                contentHtml = "";

                is_reuse_getted = true;
                uniqid = ueuse["uniqid"];
                userid = ueuse["userdata"]["userid"];
                username = ueuse["userdata"]["username"];
                iconurl = ueuse["userdata"]["iconurl"];
                datetime = ueuse["datetime"];
                favoritecount = ueuse["favoritecount"];
                replycount = ueuse["replycount"];
                reusecount = ueuse["reusecount"];
                is_favorite = ueuse["is_favorite"];
                is_bookmark = ueuse["is_bookmark"];

                is_nsfw = ueuse["nsfw"];

                img1 = ueuse["photo1"];
                img2 = ueuse["photo2"];
                img3 = ueuse["photo3"];
                img4 = ueuse["photo4"];
                vid1 = ueuse["video1"];

                abi = ueuse["abi"]["abi_text"];
                abi_date = ueuse["abi"]["abi_date"];
            }
        }

    } else if (ueuse["type"] == "Reply") {
        check = getCheckIcon(ueuse["userdata"]);
        bot = getBotIcon(ueuse["userdata"]);
        AIBlock = getAIBlockFlag(ueuse["userdata"]);

        if (selectedUniqid != null && selectedUniqid == ueuse["uniqid"]) {
            reuse = `<div class="rp"><div class="here"></div><div class="totop"></div><p>一番上のユーズに返信</p></div>`;
        } else {
            reuse = `<div class="rp"><div class="totop"></div><p>一番上のユーズに返信</p></div>`;
        }

        inyo = ``;
        contentHtml = formatMarkdown(ueuse["ueuse"]);

        uniqid = ueuse["uniqid"];
        userid = ueuse["userdata"]["userid"];
        username = ueuse["userdata"]["username"];
        iconurl = ueuse["userdata"]["iconurl"];
        datetime = ueuse["datetime"];
        favoritecount = ueuse["favoritecount"];
        replycount = ueuse["replycount"];
        reusecount = ueuse["reusecount"];
        is_favorite = ueuse["is_favorite"];
        is_bookmark = ueuse["is_bookmark"];

        is_nsfw = ueuse["nsfw"];

        img1 = ueuse["photo1"];
        img2 = ueuse["photo2"];
        img3 = ueuse["photo3"];
        img4 = ueuse["photo4"];
        vid1 = ueuse["video1"];

        abi = ueuse["abi"]["abi_text"];
        abi_date = ueuse["abi"]["abi_date"];
    } else if (ueuse["type"] == "User") {
        html = `
            <div class="ueuse">
                <div class="headbox">
                    <a href="/@`+ ueuse["userdata"]["userid"] + `">
                        <img src="`+ ueuse["userdata"]["headurl"] + `" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                    </a>
                </div>
                <div class="flebox">
                    <div class="user">
                        <a href="/@`+ ueuse["userdata"]["userid"] + `">
                            <img src="`+ ueuse["userdata"]["iconurl"] + `" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/icon_404.png'">
                        </a>
                        <div class="u_name">
                            <a href="/@`+ ueuse["userdata"]["userid"] + `">` + ueuse["userdata"]["username"] + `</a>
                        </div>
                        <div class="idbox">
                            <a href="/@`+ ueuse["userdata"]["userid"] + `">@` + ueuse["userdata"]["userid"] + `</a>
                        </div>
                    </div>
                </div>
                
                <div class="profilebox">
                    <p>
                    `+ ueuse["userdata"]["profile"] + `
                    </p>
                </div>
            </div>
        `;
        return html;
    } else {
        check = getCheckIcon(ueuse["userdata"]);
        bot = getBotIcon(ueuse["userdata"]);
        AIBlock = getAIBlockFlag(ueuse["userdata"]);

        reuse = ``;
        inyo = ``;
        contentHtml = formatMarkdown(ueuse["ueuse"]);

        uniqid = ueuse["uniqid"];
        userid = ueuse["userdata"]["userid"];
        username = ueuse["userdata"]["username"];
        iconurl = ueuse["userdata"]["iconurl"];
        datetime = ueuse["datetime"];
        favoritecount = ueuse["favoritecount"];
        replycount = ueuse["replycount"];
        reusecount = ueuse["reusecount"];
        is_favorite = ueuse["is_favorite"];
        is_bookmark = ueuse["is_bookmark"];

        is_nsfw = ueuse["nsfw"];

        img1 = ueuse["photo1"];
        img2 = ueuse["photo2"];
        img3 = ueuse["photo3"];
        img4 = ueuse["photo4"];
        vid1 = ueuse["video1"];

        abi = ueuse["abi"]["abi_text"];
        abi_date = ueuse["abi"]["abi_date"];
    }

    if (abi != "" && typeof abi === "string") {
        abi = formatMarkdown(abi);
        abi = await replaceMentions(abi);
        abi = await replaceCustomEmojis(abi);

        abi_html = `<div class="abi">
                        <div class="back">
                        <h1>`+ await replaceCustomEmojis(username) + `さんが追記しました</h1>
                        </div><p>`+ abi + `</p>
                        <div class="h3s">`+ formatSmartDate(abi_date) + `</div>
                    </div>`;
        addabi = ``;
    } else {
        abi_html = ``;
        if (global_userid == userid) {
            addabi = `<button name="addabi" id="addabi" data-uniqid2="` + uniqid + `" class="addabi"><svg><use xlink:href="../img/sysimage/addabi_1.svg#addabi_1"></use></svg></button>`;
        } else {
            addabi = ``;
        }
    }

    let is_fav = {
        "class": "favbtn",
        "icon": "../img/sysimage/favorite_1.svg#favorite"
    };
    if (is_favorite === true) {
        is_fav = {
            "class": "favbtn favbtn_after",
            "icon": "../img/sysimage/favorite_2.svg#favorite"
        };
    }

    let is_reu = {
        "class": "reuse"
    };
    if (ueuse["type"] == "Reuse") {
        if (!(ueuse["ueuse"].length > 0)) {
            if (global_userid == ueuse["userdata"]["userid"]) {
                is_reu = {
                    "class": "reuse reuse_after"
                };
            }
        }
    }

    let is_bok = {
        "class": "bookmark",
        "icon": "../img/sysimage/bookmark_1.svg#bookmark_1"
    };
    if (is_bookmark === true) {
        is_bok = {
            "class": "bookmark bookmark_after",
            "icon": "../img/sysimage/bookmark_1.svg#bookmark_1"
        };
    }

    if (is_nsfw == true) {
        nsfw_html = `<div class="nsfw" data-uniqid="` + uniqid + `" id="nsfw">
                        <p>NSFW指定がされている投稿です！<br>職場や公共の場での表示には適さない場合があります。<br>表示ボタンを押すと表示されます。</p>
                        <div class="btnzone">
                            <input type="button" id="nsfw_view" class="mini_irobtn" value="表示">
                        </div>
                    </div>`
        nsfw_start_html = `<div class="nsfw_main" data-uniqid="` + uniqid + `"><div class="block">`
        nsfw_end_html = `</div></div>`
    }

    if (img1.length > 0) {
        if (img2.length > 0) {
            if (img3.length > 0) {
                if (img4.length > 0) {
                    img_html = `<div class="photo4">
                        <a>
                            <img src="`+ img1 + `" alt="画像1" title="画像1" data-id="1" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                        </a>
                        <a>
                            <img src="`+ img2 + `" alt="画像2" title="画像2" data-id="2" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                        </a>
                        <a>
                            <img src="`+ img3 + `" alt="画像3" title="画像3" data-id="3" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                        </a>
                        <a>
                            <img src="`+ img4 + `" alt="画像4" title="画像4" data-id="4" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                        </a>
                    </div>`;
                } else {
                    img_html = `<div class="photo3">
                                    <a>
                                        <img src="`+ img1 + `" alt="画像1" title="画像1" data-id="1" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                                    </a>
                                    <a>
                                        <img src="`+ img2 + `" alt="画像2" title="画像2" data-id="2" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                                    </a>
                                    <div class="photo3_btm">
                                        <a>
                                            <img src="`+ img3 + `" alt="画像3" title="画像3" data-id="3" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                                        </a>
                                    </div>
                                </div>`;
                }
            } else {
                img_html = `<div class="photo2">
                                <a>
                                    <img src="`+ img1 + `" alt="画像1" title="画像1" data-id="1" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                                </a>
                                <a>
                                    <img src="`+ img2 + `" alt="画像2" title="画像2" data-id="2" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                                </a>
                            </div>`;
            }
        } else {
            img_html = `<div class="photo1">
                            <a>
                                <img src="`+ img1 + `" alt="画像1" title="画像1" data-id="1" `+ AIBlock + ` id="ueuse_image" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                            </a>
                        </div>`;
        }
    } else {
        img_html = ``;
    }

    if (vid1.length > 0) {
        vid_html = `<div class="video1">
                        <video controls="" src="`+ vid1 + `"></video>
                    </div>`;
    }

    // カスタム絵文字を非同期に差し替え
    contentHtml = await replaceMentions(contentHtml);
    contentHtml = await replaceCustomEmojis(contentHtml);

    if (ueuse["type"] == "Reuse") {
        if (ueuse["ueuse"].length > 0) {
            if (YouTube_and_nicovideo_Links(ueuse["ueuse"])) {
                contentHtml = contentHtml + YouTube_and_nicovideo_Links(ueuse["ueuse"]);
            }
        } else {
            if (ueuse["reuse"] != null) {
                if (YouTube_and_nicovideo_Links(ueuse["reuse"]["ueuse"])) {
                    contentHtml = contentHtml + YouTube_and_nicovideo_Links(ueuse["reuse"]["ueuse"]);
                }
            }

        }

    } else {
        if (YouTube_and_nicovideo_Links(ueuse["ueuse"])) {
            contentHtml = contentHtml + YouTube_and_nicovideo_Links(ueuse["ueuse"]);
        }
    }
    var favbox = `
        <hr>
        <div class="favbox">
            <button class="`+ is_fav["class"] + `" id="favbtn" data-uniqid="` + uniqid + `" data-userid2="` + userid + `"><svg><use xlink:href="` + is_fav["icon"] + `" alt="いいね"></use></svg><span class="like-count">` + favoritecount + `</span></button>
            <button name="reusebtn" id="reusebtn" class="`+ is_reu["class"] + `" data-uniqid="` + ueuse["uniqid"] + `" data-userid="` + userid + `"><svg><use xlink:href="../img/sysimage/reuse_1.svg#reuse_1"></use></svg><span class="like-count">` + reusecount + `</span></button>
            <a href="/!`+ uniqid + `" class="tuduki"><svg><use xlink:href="../img/sysimage/reply_1.svg#reply_1"></use></svg>` + replycount + `</a>
            
            <button name="bookmark" id="bookmark" class="`+ is_bok["class"] + `" data-uniqid="` + uniqid + `" data-userid="` + userid + `"><svg><use xlink:href="../img/sysimage/bookmark_1.svg#bookmark_1"></use></svg></button>
            `+ addabi + `
            <button name="popup" id="popup" class="etcbtn" data-uniqid="`+ uniqid + `" data-userid="` + userid + `"><svg><use xlink:href="../img/sysimage/etc_1.svg#etc_1"></use></svg></button>
        </div>
        `

    if (ueuse["is_activitypub"] == true) {
        favbox = "";
    }

    if (is_reuse_getted != true) {
        html = `
            <div class="ueuse" id="ueuse-`+ ueuse["uniqid"] + `">
                `+ reuse + `
                <div class="flebox">
                    <a href="/@`+ userid + `"><img src="` + iconurl + `" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/icon_404.png'"></a>
                    <a href="/@`+ userid + `"><div class="u_name">` + await replaceCustomEmojis(username) + `</div></a>
                    <div class="idbox">
                        <a href="/@`+ userid + `">@` + userid + `</a>
                    </div>
                    `+ bot + `
                    `+ check + `
                    <div class="time">`+ formatSmartDate(datetime) + `</div>
                </div>
                `+ nsfw_html + `
                `+ nsfw_start_html + `
                <div class="content">`+ contentHtml + `</div>
                `+ img_html + `
                `+ vid_html + `
                `+ inyo + `
                `+ abi_html + `
                `+ nsfw_end_html + `
                `+ favbox + `
            </div>
        `;
    } else {
        html = `
            <div class="ueuse" id="ueuse-`+ ueuse["uniqid"] + `">
                `+ reuse + `
                `+ inyo + `
            </div>
        `;
    }

    return html;
}
function createAdsHtml(ads) {
    if (!(ads == null || ads == "")) {
        var ads_html = `<div class="ads">
                            <a href="`+ ads["url"] + `" target="_blank">
                                <img src="`+ ads["imgurl"] + `" title="` + ads["memo"] + `" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/image_404.png'">
                            </a>
                        </div>`;
        return ads_html;
    } else {
        var ads_html = ``;
        return ads_html;
    }
}

// 投稿一覧を非同期で全部HTML化 → そのあと順番通りにappend
async function renderUeuses(ueuseData, selectedUniqid = null) {
    if (ueuseData["success"] == false) {
        var errmsg;
        if (ueuseData["error"] == "no_ueuse") {
            errmsg = "ユーズがありません";
        } else if (ueuseData["error"] == "bad_request") {
            errmsg = "不正なリクエストが検出されました";
        }
        $("#postContainer").append(`<div class="tokonone" id="noueuse"><p>` + errmsg + `</p></div>`);
        return true;
    } else {
        var htmlList = [];
        var ueuseList = ueuseData["ueuses"];
        for (const ueuse of ueuseList) {
            const html = await createUeuseHtml(ueuse, selectedUniqid);
            htmlList.push(html);
        }

        var ads = ueuseData["ads"];
        const ads_html = createAdsHtml(ads);
        htmlList.push(ads_html);

        // 投稿順を保ったままDOMへ追加
        for (const html of htmlList) {
            $("#postContainer").append(html);
        }
        return true;
    }
}


async function createNotificationHtml(notification) {
    let html = "";
    let is_readclass = "";
    let datetime = notification["datetime"];
    let userid = notification["userdata"]["userid"];

    let userid_url = "";
    if(userid == "uwuzu-fromsys"){
        userid_url = "/rule/serverabout";
    }else{
        userid_url = "/@"+userid;
    }

    let username = notification["userdata"]["username"];
    let iconurl = notification["userdata"]["iconurl"];
    let title = notification["title"];
    let content = formatMarkdown(notification["message"]);
    content = await replaceMentions(content);
    content = await replaceCustomEmojis(content);

    let url = notification["url"];

    if (notification["is_read"] == false) {
        is_readclass = "this";
    }

    html = `
        <div class="notification `+ is_readclass + `">
            <div class="flebox">
                <div class="time">`+ formatSmartDate(datetime) + `</div>
            </div>
            <div class="flebox">
                <div class="icon">
                    <a href="`+ userid_url + `">
                        <img src="`+ iconurl + `" onerror="this.onerror=null;this.src='../img/sysimage/errorimage/icon_404.png'">
                    </a>
                </div>
                <div class="username">
                    <a href="`+ userid_url + `">` + await replaceCustomEmojis(username) + `</a>
                </div>
            </div>
            <h3>`+ await replaceCustomEmojis(title) + `</h3>
            <p>`+ content + `</p>
            <a href="`+ url + `">詳細をみる</a>
        </div>
    `;
    return html;
}

async function renderNotifications(notificationData) {
    if (notificationData["success"] == false) {
        var errmsg;
        if (notificationData["error"] == "no_notification") {
            errmsg = "通知がありません";
        } else if (notificationData["error"] == "bad_request") {
            errmsg = "不正なリクエストが検出されました";
        }
        $("#postContainer").append(`<div class="tokonone" id="noueuse"><p>` + errmsg + `</p></div>`);
        return true;
    } else {
        var htmlList = [];
        var notificationList = notificationData["notifications"];

        for (const notification of notificationList) {
            const html = await createNotificationHtml(notification);
            htmlList.push(html);
        }

        // 投稿順を保ったままDOMへ追加
        for (const html of htmlList) {
            $("#postContainer").append(html);
        }
        return true;
    }
}