$(document).on('click', '.mini_irobtn', function (event) {
    event.preventDefault();
    mother = $(this).parent();
    mother2 = $(mother).parent();
    $(mother2).next('.nsfw_main').children().removeClass('block');
    $(mother2).next('.nsfw_main').children().addClass('clear');

    $(mother2).next('.nsfw_main').removeClass('nsfw_main');
    $(mother2).hide();
});


$(document).on('click', '#ueuse_image', function (event) {
    var imgLink = $(this).attr('src');
    var imgAIBlock = $(this).attr('data-aiblock');

    var modal = $('#Big_ImageModal');
    var modalMain = $('.modal-content');
    var modalimg_zone = $('#Big_ImageMain');

    $(modalimg_zone).attr('src',imgLink);

    if(imgAIBlock == "true"){
        $("#NoAI_Footer").show();
    }else{
        $("#NoAI_Footer").hide();
    }
    
    modal.show();
    modalMain.addClass("slideUp");
    modalMain.removeClass("slideDown");

    modal.on('click', function() {
        modalMain.removeClass("slideUp");
        modalMain.addClass("slideDown");
        window.setTimeout(function(){
            modal.hide();
        }, 150);
    });
});

function view_notify(notify){
    $("#notify").children("p").text(notify);
    $("#notify").show();
    setTimeout(function(){
        $("#notify").hide();
    }, 10000);
}

function isHarmfulContent(text, examples, keywords, similarityThreshold = 0.7) {
    var total_score = 0;
    // レーベンシュタイン距離を計算
    function levenshteinDistance(a, b) {
        const dp = Array(a.length + 1).fill().map(() => Array(b.length + 1).fill(0));
        for (let i = 0; i <= a.length; i++) dp[i][0] = i;
        for (let j = 0; j <= b.length; j++) dp[0][j] = j;
        for (let i = 1; i <= a.length; i++) {
            for (let j = 1; j <= b.length; j++) {
                const cost = a[i - 1] === b[j - 1] ? 0 : 1;
                dp[i][j] = Math.min(dp[i - 1][j] + 1, dp[i][j - 1] + 1, dp[i - 1][j - 1] + cost);
            }
        }
        return dp[a.length][b.length];
    }

    // 類似度スコアを計算
    function similarityScore(a, b) {
        const distance = levenshteinDistance(a, b);
        const maxLength = Math.max(a.length, b.length);
        return maxLength === 0 ? 0 : 1 - distance / maxLength;
    }

    // 文脈的な有害性を判定
    function hasHarmfulContext(text, keywords, examples) {
        const normalizedText = text.replace(/[！。！？、]/g, '');
        
        let harmfulCharCount = 0;
        let nonHarmfulCharCount = normalizedText.length;
    
        const combinedPattern = new RegExp([...keywords, ...examples].join('|'), 'g');
        const matches = normalizedText.match(combinedPattern);
        if (matches) {
            const harmfulText = matches.join('');
            harmfulCharCount += harmfulText.length;
            nonHarmfulCharCount -= harmfulText.length;
        }

        if (harmfulCharCount > nonHarmfulCharCount || harmfulCharCount > 6) {
            return true;
        } else {
            return false;
        }
    }
    
    if (hasHarmfulContext(text, keywords, examples)){
        total_score += 1;
    }

    // 類似度スコアチェック
    const textScore = examples.map(example => similarityScore(text, example)); 
    const maxScore = Math.max(...textScore);

    if (maxScore >= similarityThreshold) {
        total_score += 1;
    }

    if(total_score >= 1){
        return true;
    }else{
        return false;
    }
}

function check_Harmful_ueuse(text){
    const examples = ["お前なんかいらない", "死んでしまえ", "無能すぎる", "もう関わるな", "お前マジ死ね", "死ねばいいのに", "死んだほうがいいよ", "よく恥ずかしくないね", "社会の迷惑", "存在価値ないだろ", "死んでくれ", "ほんと馬鹿だから", "話しかけてくんな", "口聞かない", "許さないからな", "なんで生きてるの", "早く死ねよ", "凍結されろ", "BANされろ"];
    const keywords = ["死ね", "バカ", "馬鹿", "嫌い", "クズ", "ゴミ", "低能", "無能", "関わるな", "いらない", "消えろ", "殺す", "来るな", "死んで", "死刑", "Fuck", "しね", "下手", "カス", "ますが", "ですが", "ですが何か", "かよ", "荒らし", "黙って", "黙れ", "凍結されろ", "BANされろ"];
    return isHarmfulContent(text, examples, keywords)
}