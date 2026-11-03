<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>年度影音游总结海报生成器</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* 自定义滚动条 */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.4); }
        ::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.4); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.7); }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500 selection:text-white">

    <!-- 顶部导航栏 -->
    <header class="border-b border-white/10 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center font-bold text-lg shadow-lg shadow-indigo-500/20">
                    26
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-bold tracking-tight">Annual Board · 年度影音游总结</h1>
                    <p class="text-xs text-slate-400 hidden sm:block">搜索并选择你的年度代表作，一键生成 9:16 竖向四宫格总结海报</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    onclick="fillDemoData()"
                    class="text-xs sm:text-sm px-3 py-1.5 rounded-lg border border-white/15 bg-white/5 hover:bg-white/10 text-slate-300 transition"
                >
                    填入示例数据
                </button>
                <button
                    type="button"
                    onclick="generateAndDownloadPoster()"
                    class="text-xs sm:text-sm px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 hover:opacity-95 font-semibold text-white shadow-lg shadow-indigo-500/25 transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>生成并下载海报</span>
                </button>
            </div>
        </div>
    </header>

    <!-- 主体布局 -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- 左侧：四个选择框与配置区 (占 7 列) -->
            <div class="lg:col-span-7 space-y-6">

                <!-- 基础设置卡片 -->
                <section class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 shadow-xl">
                    <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                        海报主题与背景设置
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1.5">海报主标题</label>
                            <input
                                id="posterHeadingInput"
                                type="text"
                                value="2026 我的年度精神食粮"
                                oninput="updatePosterHeader()"
                                class="w-full rounded-xl bg-slate-950/90 border border-white/10 px-3.5 py-2 text-sm text-white focus:outline-none focus:border-indigo-500 transition"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1.5">渐变大背景风格</label>
                            <div class="flex items-center gap-2.5 pt-1" id="themeSelector">
                                <button type="button" onclick="setTheme('aurora')" data-theme="aurora" class="theme-btn w-8 h-8 rounded-full bg-gradient-to-br from-slate-900 via-indigo-900 to-fuchsia-900 ring-2 ring-white ring-offset-2 ring-offset-slate-950 transition" title="极光星夜"></button>
                                <button type="button" onclick="setTheme('sunset')" data-theme="sunset" class="theme-btn w-8 h-8 rounded-full bg-gradient-to-br from-rose-950 via-amber-900 to-purple-950 opacity-70 hover:opacity-100 transition" title="落日流金"></button>
                                <button type="button" onclick="setTheme('emerald')" data-theme="emerald" class="theme-btn w-8 h-8 rounded-full bg-gradient-to-br from-slate-950 via-emerald-900 to-cyan-900 opacity-70 hover:opacity-100 transition" title="深海翡翠"></button>
                                <button type="button" onclick="setTheme('crimson')" data-theme="crimson" class="theme-btn w-8 h-8 rounded-full bg-gradient-to-br from-zinc-950 via-red-950 to-amber-950 opacity-70 hover:opacity-100 transition" title="黑金烈焰"></button>
                                <button type="button" onclick="setTheme('ocean')" data-theme="ocean" class="theme-btn w-8 h-8 rounded-full bg-gradient-to-br from-blue-950 via-sky-900 to-indigo-950 opacity-70 hover:opacity-100 transition" title="深蓝星海"></button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 四个分类选择框 (2x2 网格表单) -->
                <section class="grid grid-cols-1 md:grid-cols-2 gap-5" id="selectorCardsContainer">
                    <!-- 由 JS 动态渲染 4 个选择框: 游戏(game)、电影(movie)、剧集(tv)、音乐(music) -->
                </section>

                <!-- 底部生成大按钮 -->
                <div class="pt-2">
                    <button
                        type="button"
                        onclick="generateAndDownloadPoster()"
                        class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 hover:opacity-95 font-bold text-base text-white shadow-xl shadow-indigo-500/25 transition flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>生成 9:16 竖向四宫格海报</span>
                    </button>
                </div>
            </div>

            <!-- 右侧：9:16 竖向海报实时预览区 (占 5 列) -->
            <div class="lg:col-span-5 flex flex-col items-center lg:sticky lg:top-24">
                <div class="w-full max-w-[390px]">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <span class="text-xs font-medium text-slate-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            9:16 竖版海报实时预览
                        </span>
                        <span class="text-xs text-slate-500">导出分辨率: 1080 × 1920</span>
                    </div>

                    <!-- 9:16 竖版海报容器 -->
                    <div
                        id="posterPreview"
                        class="relative w-full aspect-[9/16] rounded-3xl overflow-hidden shadow-2xl border border-white/15 flex flex-col justify-between p-6 select-none transition-colors duration-500"
                        style="background: linear-gradient(145deg, #0f172a 0%, #312e81 52%, #581c87 100%);"
                    >
                        <!-- 背景装饰光晕 -->
                        <div class="pointer-events-none absolute -top-20 -left-20 w-56 h-56 rounded-full bg-white/10 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-24 -right-20 w-64 h-64 rounded-full bg-pink-500/15 blur-3xl"></div>

                        <!-- 海报顶部标题 -->
                        <div class="relative z-10 text-center pt-1">
                            <div class="inline-block px-3 py-0.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/15 text-[10px] tracking-widest uppercase text-white/80 mb-1.5">
                                ANNUAL SUMMARY BOARD
                            </div>
                            <h2 id="posterHeadingPreview" class="text-xl font-extrabold tracking-tight text-white drop-shadow">
                                2026 我的年度精神食粮
                            </h2>
                        </div>

                        <!-- 核心：四宫格展示区 (2x2) -->
                        <div class="relative z-10 grid grid-cols-2 gap-3.5 my-auto py-3" id="posterGridPreview">
                            <!-- 由 JS 同步渲染四个宫格 -->
                        </div>

                        <!-- 海报底部脚标 -->
                        <div class="relative z-10 flex items-center justify-between text-[10px] text-white/60 border-t border-white/15 pt-2.5">
                            <span>GENERATED BY ANNUAL BOARD</span>
                            <span id="posterDatePreview">2026</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- 生成结果弹窗 (高清 Canvas PNG 预览与下载) -->
    <div id="posterModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-white/15 rounded-3xl max-w-md w-full p-5 shadow-2xl flex flex-col items-center">
            <div class="w-full flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-white">海报已生成（长按或点击下方按钮保存）</h3>
                <button type="button" onclick="closePosterModal()" class="text-slate-400 hover:text-white p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="w-full max-h-[68vh] overflow-y-auto flex justify-center bg-slate-950/60 rounded-2xl p-3 border border-white/5">
                <img id="generatedPosterImg" src="" alt="Generated Poster" class="w-auto max-h-[62vh] rounded-xl shadow-lg">
            </div>
            <div class="w-full flex gap-3 mt-4">
                <button
                    type="button"
                    onclick="closePosterModal()"
                    class="flex-1 py-2.5 rounded-xl border border-white/15 text-xs sm:text-sm text-slate-300 hover:bg-white/5 transition"
                >
                    继续修改
                </button>
                <a
                    id="downloadPosterLink"
                    href="#"
                    download="annual-board-2026.png"
                    class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-pink-500 text-xs sm:text-sm font-semibold text-white text-center shadow-lg hover:opacity-95 transition"
                >
                    下载高清 PNG (1080×1920)
                </a>
            </div>
        </div>
    </div>

    <script>
        // 四个固定分类配置
        const SLOTS = [
            { key: 'game',  label: '年度游戏', badge: 'GAME',  placeholder: '搜游戏，如：艾尔登法环 / 黑神话' },
            { key: 'movie', label: '年度电影', badge: 'MOVIE', placeholder: '搜电影，如：星际穿越 / 盗梦空间' },
            { key: 'tv',    label: '年度剧集', badge: 'TV',    placeholder: '搜剧集，如：绝命毒师 / 漫长的季节' },
            { key: 'music', label: '年度音乐', badge: 'MUSIC', placeholder: '搜音乐，如：晴天 / 七里香' },
        ];

        // 渐变主题配色定义（同时用于 CSS 预览与 Canvas 1080x1920 渲染）
        const THEMES = {
            aurora:  { stops: ['#0f172a', '#312e81', '#581c87'] },
            sunset:  { stops: ['#4c0519', '#9a3412', '#581c87'] },
            emerald: { stops: ['#022c22', '#065f46', '#1e3a8a'] },
            crimson: { stops: ['#09090b', '#450a0a', '#422006'] },
            ocean:   { stops: ['#082f49', '#1d4ed8', '#1e1b4b'] },
        };

        let currentTheme = 'aurora';

        // 当前已选中的四宫格状态
        const selectedState = {
            game:  { title: '', subtitle: '', release_year: '', cover_url: '' },
            movie: { title: '', subtitle: '', release_year: '', cover_url: '' },
            tv:    { title: '', subtitle: '', release_year: '', cover_url: '' },
            music: { title: '', subtitle: '', release_year: '', cover_url: '' },
        };

        // 防抖定时器
        const searchTimers = {};

        // 初始化渲染选择框和海报预览
        function initApp() {
            renderSelectorCards();
            renderPosterGrid();
        }

        // 渲染左侧 4 个搜索选择框
        function renderSelectorCards() {
            const container = document.getElementById('selectorCardsContainer');
            container.innerHTML = SLOTS.map(slot => `
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-4 shadow-lg relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                    ${slot.badge}
                                </span>
                                <h3 class="text-sm font-bold text-white">${slot.label}</h3>
                            </div>
                            <span id="status-${slot.key}" class="text-[11px] text-slate-400"></span>
                        </div>

                        <!-- 搜索输入框 -->
                        <div class="relative">
                            <input
                                id="search-input-${slot.key}"
                                type="text"
                                placeholder="${slot.placeholder}"
                                oninput="onSearchInput('${slot.key}', this.value)"
                                onkeydown="if(event.key==='Enter'){ event.preventDefault(); triggerSearch('${slot.key}'); }"
                                class="w-full rounded-xl bg-slate-950/90 border border-white/10 pl-3.5 pr-16 py-2 text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition"
                            >
                            <button
                                type="button"
                                onclick="triggerSearch('${slot.key}')"
                                class="absolute right-1.5 top-1.5 bottom-1.5 px-3 rounded-lg bg-indigo-600/80 hover:bg-indigo-500 text-xs font-medium text-white transition"
                            >
                                搜索
                            </button>

                            <!-- 下拉搜索结果浮层 -->
                            <div
                                id="dropdown-${slot.key}"
                                class="hidden absolute left-0 right-0 top-full mt-1.5 max-h-64 overflow-y-auto rounded-xl border border-white/15 bg-slate-900 shadow-2xl z-20 divide-y divide-white/5"
                            ></div>
                        </div>
                    </div>

                    <!-- 当前已选中条目展示与微调 -->
                    <div class="mt-3.5 pt-3 border-t border-white/5 flex items-center gap-3">
                        <div class="w-12 h-16 rounded-lg bg-slate-800 border border-white/10 overflow-hidden shrink-0 flex items-center justify-center">
                            <img
                                id="selected-cover-${slot.key}"
                                src=""
                                alt=""
                                class="hidden w-full h-full object-cover"
                                onerror="this.classList.add('hidden'); document.getElementById('empty-icon-${slot.key}').classList.remove('hidden');"
                            >
                            <span id="empty-icon-${slot.key}" class="text-[10px] text-slate-500 text-center px-1">未选择</span>
                        </div>
                        <div class="flex-1 min-w-0 space-y-1.5">
                            <input
                                id="selected-title-${slot.key}"
                                type="text"
                                placeholder="作品名称（选中后自动填入，也可手填）"
                                oninput="onManualTitleChange('${slot.key}', this.value)"
                                class="w-full rounded-lg bg-slate-950/60 border border-white/10 px-2.5 py-1 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                            >
                            <input
                                id="selected-cover-input-${slot.key}"
                                type="text"
                                placeholder="封面图片 URL（选中后自动填入）"
                                oninput="onManualCoverChange('${slot.key}', this.value)"
                                class="w-full rounded-lg bg-slate-950/60 border border-white/10 px-2.5 py-1 text-[11px] text-slate-400 placeholder-slate-600 focus:outline-none focus:border-indigo-500"
                            >
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // 渲染右侧 9:16 海报中的 2x2 四宫格
        function renderPosterGrid() {
            const grid = document.getElementById('posterGridPreview');
            grid.innerHTML = SLOTS.map(slot => {
                const item = selectedState[slot.key];
                const displayTitle = item.title ? item.title : '待选择';
                const hasCover = Boolean(item.cover_url);
                const proxiedUrl = hasCover ? getProxiedImageUrl(item.cover_url) : '';

                return `
                    <div class="flex flex-col bg-black/25 backdrop-blur-md rounded-2xl p-2 border border-white/15 shadow-lg">
                        <!-- 竖向封面图区域 -->
                        <div class="relative w-full aspect-[3/4] rounded-xl overflow-hidden bg-slate-900/80 border border-white/10">
                            ${hasCover ? `
                                <img
                                    src="${escapeHtml(proxiedUrl)}"
                                    alt="${escapeHtml(displayTitle)}"
                                    class="w-full h-full object-cover"
                                    onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');"
                                >
                                <div class="hidden absolute inset-0 flex flex-col items-center justify-center p-2 text-center bg-slate-900/90">
                                    <span class="text-[10px] text-white/40 uppercase">${slot.badge}</span>
                                    <span class="text-xs font-bold text-white/80 mt-1 line-clamp-2">${escapeHtml(displayTitle)}</span>
                                </div>
                            ` : `
                                <div class="absolute inset-0 flex flex-col items-center justify-center p-2 text-center">
                                    <span class="text-[10px] font-semibold tracking-wider text-white/30 uppercase">${slot.badge}</span>
                                    <span class="text-[11px] text-white/40 mt-1">请在左侧选择${slot.label}</span>
                                </div>
                            `}
                        </div>

                        <!-- 图片下方：年度xx：title -->
                        <div class="mt-2 px-1 text-left">
                            <p class="text-[11px] sm:text-xs font-bold text-white truncate" title="${escapeHtml(slot.label + '：' + displayTitle)}">
                                <span class="text-indigo-200/90 font-semibold">${slot.label}：</span>${escapeHtml(displayTitle)}
                            </p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 搜索输入防抖
        function onSearchInput(type, value) {
            clearTimeout(searchTimers[type]);
            const keyword = value.trim();
            if (!keyword) {
                hideDropdown(type);
                return;
            }
            searchTimers[type] = setTimeout(() => {
                triggerSearch(type);
            }, 420);
        }

        // 调用后端 /api/media/search 接口
        async function triggerSearch(type) {
            const input = document.getElementById(`search-input-${type}`);
            const statusEl = document.getElementById(`status-${type}`);
            const dropdown = document.getElementById(`dropdown-${type}`);
            const keyword = input.value.trim();

            if (!keyword) {
                hideDropdown(type);
                return;
            }

            statusEl.textContent = '搜索中...';
            dropdown.classList.remove('hidden');
            dropdown.innerHTML = `<div class="p-3 text-xs text-slate-400 text-center">正在检索「${escapeHtml(keyword)}」...</div>`;

            try {
                const url = `/api/media/search?type=${encodeURIComponent(type)}&keyword=${encodeURIComponent(keyword)}`;
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                const res = await response.json();

                if (!response.ok || res.code !== 0) {
                    throw new Error(res.message || '请求失败');
                }

                const list = res.data || [];
                const hitSource = res.meta?.hit_source || '';
                const provider = res.meta?.provider || '';
                statusEl.textContent = `${provider ? provider.toUpperCase() : ''} · ${hitSource === 'cache' ? '缓存秒回' : '接口返回'} (${list.length})`;

                if (list.length === 0) {
                    dropdown.innerHTML = `<div class="p-3 text-xs text-slate-400 text-center">未找到相关结果，可直接下方手动输入名称</div>`;
                    return;
                }

                // 缓存候选数据到全局临时映射以便点击选择
                window.__searchResults = window.__searchResults || {};
                window.__searchResults[type] = list;

                dropdown.innerHTML = list.map((item, idx) => {
                    const thumb = item.cover_url ? getProxiedImageUrl(item.cover_url) : '';
                    return `
                        <button
                            type="button"
                            onclick="selectMediaItem('${type}', ${idx})"
                            class="w-full text-left p-2.5 hover:bg-indigo-500/20 transition flex items-center gap-3"
                        >
                            <div class="w-10 h-14 rounded bg-slate-800 overflow-hidden shrink-0 flex items-center justify-center border border-white/10">
                                ${thumb ? `<img src="${escapeHtml(thumb)}" class="w-full h-full object-cover" onerror="this.style.display='none'">` : `<span class="text-[9px] text-slate-500">无封面</span>`}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <p class="text-xs font-bold text-white truncate">${escapeHtml(item.title || '')}</p>
                                    ${item.release_year ? `<span class="text-[10px] text-slate-400 shrink-0">(${escapeHtml(item.release_year)})</span>` : ''}
                                </div>
                                <p class="text-[11px] text-slate-400 truncate mt-0.5">${escapeHtml(item.subtitle || item.original_title || '')}</p>
                            </div>
                        </button>
                    `;
                }).join('');
            } catch (err) {
                statusEl.textContent = '搜索异常';
                dropdown.innerHTML = `<div class="p-3 text-xs text-rose-400 text-center">搜索出错：${escapeHtml(err.message)}</div>`;
            }
        }

        // 选中某一项候选结果，自动填入封面与名称
        function selectMediaItem(type, index) {
            const list = (window.__searchResults && window.__searchResults[type]) || [];
            const item = list[index];
            if (!item) return;

            selectedState[type] = {
                title: item.title || '',
                subtitle: item.subtitle || '',
                release_year: item.release_year || '',
                cover_url: item.cover_url || '',
            };

            syncSlotUI(type);
            hideDropdown(type);
            renderPosterGrid();
        }

        // 同步单个选择框的已选展示
        function syncSlotUI(type) {
            const state = selectedState[type];
            const titleInput = document.getElementById(`selected-title-${type}`);
            const coverInput = document.getElementById(`selected-cover-input-${type}`);
            const coverImg = document.getElementById(`selected-cover-${type}`);
            const emptyIcon = document.getElementById(`empty-icon-${type}`);

            titleInput.value = state.title || '';
            coverInput.value = state.cover_url || '';

            if (state.cover_url) {
                coverImg.src = getProxiedImageUrl(state.cover_url);
                coverImg.classList.remove('hidden');
                emptyIcon.classList.add('hidden');
            } else {
                coverImg.src = '';
                coverImg.classList.add('hidden');
                emptyIcon.classList.remove('hidden');
            }
        }

        function onManualTitleChange(type, value) {
            selectedState[type].title = value.trim();
            renderPosterGrid();
        }

        function onManualCoverChange(type, value) {
            selectedState[type].cover_url = value.trim();
            syncSlotUI(type);
            renderPosterGrid();
        }

        function hideDropdown(type) {
            const dropdown = document.getElementById(`dropdown-${type}`);
            if (dropdown) dropdown.classList.add('hidden');
        }

        // 点击页面空白处收起下拉框
        document.addEventListener('click', (e) => {
            SLOTS.forEach(slot => {
                const dropdown = document.getElementById(`dropdown-${slot.key}`);
                const input = document.getElementById(`search-input-${slot.key}`);
                if (dropdown && !dropdown.contains(e.target) && e.target !== input) {
                    dropdown.classList.add('hidden');
                }
            });
        });

        function updatePosterHeader() {
            const val = document.getElementById('posterHeadingInput').value.trim() || '2026 我的年度精神食粮';
            document.getElementById('posterHeadingPreview').textContent = val;
        }

        function setTheme(themeKey) {
            if (!THEMES[themeKey]) return;
            currentTheme = themeKey;
            const [c1, c2, c3] = THEMES[themeKey].stops;
            const poster = document.getElementById('posterPreview');
            poster.style.background = `linear-gradient(145deg, ${c1} 0%, ${c2} 52%, ${c3} 100%)`;

            document.querySelectorAll('.theme-btn').forEach(btn => {
                if (btn.dataset.theme === themeKey) {
                    btn.classList.add('ring-2', 'ring-white', 'ring-offset-2', 'ring-offset-slate-950');
                    btn.classList.remove('opacity-70');
                } else {
                    btn.classList.remove('ring-2', 'ring-white', 'ring-offset-2', 'ring-offset-slate-950');
                    btn.classList.add('opacity-70');
                }
            });
        }

        // 一键填入示例数据便于快速预览测试
        function fillDemoData() {
            selectedState.game = {
                title: '塞尔达传说 王国之泪',
                release_year: '2023',
                cover_url: 'https://lain.bgm.tv/pic/cover/l/62/51/325585_22528.jpg',
            };
            selectedState.movie = {
                title: '星际穿越',
                release_year: '2014',
                cover_url: 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
            };
            selectedState.tv = {
                title: '绝命毒师',
                release_year: '2008',
                cover_url: 'https://image.tmdb.org/t/p/w500/ztkUQFLlC19CCMYHW9o1zWhJRNq.jpg',
            };
            selectedState.music = {
                title: '晴天',
                release_year: '2003',
                cover_url: 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/3c/4b/98/3c4b9868-a38a-7459-6766-e6f0c1bd5628/mzi.bkozrgrx.jpg/600x600bb.jpg',
            };

            SLOTS.forEach(s => syncSlotUI(s.key));
            renderPosterGrid();
        }

        // 通过后端代理转换图片地址（解决 Canvas 跨域污染与防盗链）
        function getProxiedImageUrl(rawUrl) {
            if (!rawUrl) return '';
            if (rawUrl.startsWith('/') || rawUrl.startsWith('data:')) return rawUrl;
            return `/api/media/image-proxy?url=${encodeURIComponent(rawUrl)}`;
        }

        // 使用原生 HTML5 Canvas 绘制 1080x1920 (9:16) 高清四宫格海报
        async function generateAndDownloadPoster() {
            const width = 1080;
            const height = 1920;
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');

            // 1. 绘制 9:16 渐变大背景
            const [c1, c2, c3] = THEMES[currentTheme].stops;
            const bgGrad = ctx.createLinearGradient(0, 0, width, height);
            bgGrad.addColorStop(0, c1);
            bgGrad.addColorStop(0.52, c2);
            bgGrad.addColorStop(1, c3);
            ctx.fillStyle = bgGrad;
            ctx.fillRect(0, 0, width, height);

            // 装饰柔和光晕
            const glow1 = ctx.createRadialGradient(180, 180, 20, 180, 180, 420);
            glow1.addColorStop(0, 'rgba(255, 255, 255, 0.12)');
            glow1.addColorStop(1, 'rgba(255, 255, 255, 0)');
            ctx.fillStyle = glow1;
            ctx.fillRect(0, 0, width, height);

            // 2. 绘制顶部标题区域
            ctx.textAlign = 'center';
            ctx.fillStyle = 'rgba(255, 255, 255, 0.65)';
            ctx.font = '600 24px -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif';
            ctx.fillText('ANNUAL SUMMARY BOARD', width / 2, 130);

            const headingText = document.getElementById('posterHeadingInput').value.trim() || '2026 我的年度精神食粮';
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 58px -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif';
            ctx.fillText(headingText, width / 2, 210);

            // 3. 绘制 2x2 四宫格
            const paddingX = 72;
            const gapX = 44;
            const gapY = 48;
            const cardWidth = (width - paddingX * 2 - gapX) / 2; // 446px
            const cardHeight = 670;
            const startY = 290;

            for (let i = 0; i < SLOTS.length; i++) {
                const slot = SLOTS[i];
                const item = selectedState[slot.key];
                const col = i % 2;
                const row = Math.floor(i / 2);

                const cardX = paddingX + col * (cardWidth + gapX);
                const cardY = startY + row * (cardHeight + gapY);

                // 宫格半透明底卡
                drawRoundedRect(ctx, cardX, cardY, cardWidth, cardHeight, 36);
                ctx.fillStyle = 'rgba(0, 0, 0, 0.28)';
                ctx.fill();
                ctx.lineWidth = 2;
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.16)';
                ctx.stroke();

                // 封面图区域
                const imgX = cardX + 24;
                const imgY = cardY + 24;
                const imgW = cardWidth - 48; // 398
                const imgH = 540;

                ctx.save();
                drawRoundedRect(ctx, imgX, imgY, imgW, imgH, 24);
                ctx.clip();

                // 默认深色占位底
                ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
                ctx.fillRect(imgX, imgY, imgW, imgH);

                if (item.cover_url) {
                    const loadedImg = await loadImageSafe(getProxiedImageUrl(item.cover_url));
                    if (loadedImg) {
                        drawImageCover(ctx, loadedImg, imgX, imgY, imgW, imgH);
                    } else {
                        drawPlaceholderText(ctx, slot.badge, item.title || '加载失败', imgX, imgY, imgW, imgH);
                    }
                } else {
                    drawPlaceholderText(ctx, slot.badge, '未选择', imgX, imgY, imgW, imgH);
                }
                ctx.restore();

                // 图片下方文字：年度xx：title
                const fullLabel = `${slot.label}：${item.title || '待选择'}`;
                ctx.textAlign = 'left';
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 30px -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif';
                ctx.fillText(truncateText(ctx, fullLabel, imgW), imgX + 4, cardY + cardHeight - 36);
            }

            // 4. 绘制底部页脚
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(paddingX, 1790);
            ctx.lineTo(width - paddingX, 1790);
            ctx.stroke();

            ctx.fillStyle = 'rgba(255, 255, 255, 0.55)';
            ctx.font = '500 24px -apple-system, BlinkMacSystemFont, "PingFang SC", sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText('GENERATED BY ANNUAL BOARD', paddingX, 1840);
            ctx.textAlign = 'right';
            ctx.fillText(new Date().getFullYear().toString(), width - paddingX, 1840);

            // 导出 PNG 并打开预览弹窗
            const dataUrl = canvas.toDataURL('image/png');
            document.getElementById('generatedPosterImg').src = dataUrl;
            document.getElementById('downloadPosterLink').href = dataUrl;
            document.getElementById('posterModal').classList.remove('hidden');
        }

        function closePosterModal() {
            document.getElementById('posterModal').classList.add('hidden');
        }

        // Canvas 辅助函数：圆角矩形路径
        function drawRoundedRect(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        }

        // Canvas 辅助函数：模拟 object-fit: cover 居中裁切绘制图片
        function drawImageCover(ctx, img, x, y, w, h) {
            const imgRatio = img.width / img.height;
            const boxRatio = w / h;
            let sx = 0, sy = 0, sw = img.width, sh = img.height;

            if (imgRatio > boxRatio) {
                sw = img.height * boxRatio;
                sx = (img.width - sw) / 2;
            } else {
                sh = img.width / boxRatio;
                sy = (img.height - sh) / 2;
            }
            ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
        }

        function drawPlaceholderText(ctx, badge, text, x, y, w, h) {
            ctx.fillStyle = 'rgba(255,255,255,0.25)';
            ctx.textAlign = 'center';
            ctx.font = 'bold 28px sans-serif';
            ctx.fillText(badge, x + w / 2, y + h / 2 - 15);
            ctx.fillStyle = 'rgba(255,255,255,0.5)';
            ctx.font = '24px "PingFang SC", sans-serif';
            ctx.fillText(truncateText(ctx, text, w - 40), x + w / 2, y + h / 2 + 30);
        }

        function truncateText(ctx, text, maxWidth) {
            if (ctx.measureText(text).width <= maxWidth) return text;
            let str = text;
            while (str.length > 0 && ctx.measureText(str + '...').width > maxWidth) {
                str = str.slice(0, -1);
            }
            return str + '...';
        }

        function loadImageSafe(url) {
            return new Promise((resolve) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = () => resolve(null);
                img.src = url;
            });
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        initApp();
    </script>
</body>
</html>

