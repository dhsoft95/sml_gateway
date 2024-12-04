<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard | Payment Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .stats-gradient {
            background: linear-gradient(135deg, rgba(234, 179, 8, 0.1) 0%, rgba(234, 179, 8, 0.05) 100%);
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen">
<!-- Navigation -->
<nav class="bg-gray-800 border-b border-gray-700 sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-8">
                <a href="#" class="text-yellow-500 text-xl font-bold flex items-center">
                    <svg width="126" height="47" viewBox="0 0 126 47" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M51.3293 20.2871C50.138 19.743 49.2234 18.985 48.5865 18.012C47.9496 17.0391 47.6141 15.9098 47.5811 14.6231H51.6276C51.6441 15.5303 52.0126 16.2512 52.7321 16.7871C53.4516 17.3229 54.4488 17.5914 55.7237 17.5914C56.6672 17.5914 57.4073 17.435 57.9451 17.1214C58.483 16.8087 58.7524 16.3294 58.7524 15.6866C58.7524 15.2752 58.6409 14.9409 58.4169 14.6848C58.1939 14.4298 57.8213 14.2148 57.3 14.042C56.7787 13.8692 56.0375 13.7078 55.0785 13.5596C53.3236 13.2964 51.9631 12.9662 50.9948 12.5702C50.0265 12.1743 49.3349 11.6549 48.922 11.012C48.508 10.3692 48.3016 9.51969 48.3016 8.46444C48.3016 7.4092 48.5824 6.428 49.146 5.6196C49.7086 4.81222 50.5189 4.18895 51.5791 3.75184C52.6382 3.31472 53.879 3.09668 55.3025 3.09668C56.726 3.09668 58.0122 3.34866 59.1127 3.85057C60.2131 4.35351 61.0698 5.06215 61.682 5.97752C62.2941 6.89289 62.609 7.94402 62.6255 9.13091H58.579C58.5624 8.28959 58.2393 7.65912 57.6107 7.23846C56.982 6.81781 56.1459 6.60799 55.1033 6.60799C54.2093 6.60799 53.5063 6.74787 52.9933 7.02865C52.4803 7.30943 52.2232 7.73832 52.2232 8.31428C52.2232 8.71025 52.3388 9.02806 52.5711 9.26667C52.8023 9.50631 53.2039 9.7079 53.7747 9.87246C54.3456 10.037 55.1528 10.1944 56.1954 10.3425C57.7676 10.5564 59.0249 10.8742 59.9684 11.2949C60.9119 11.7155 61.5984 12.272 62.0288 12.9641C62.4593 13.6563 62.674 14.5223 62.674 15.5611C62.674 16.6986 62.3798 17.6839 61.7924 18.517C61.2051 19.3501 60.3772 19.9888 59.3098 20.4342C58.2424 20.8795 56.9965 21.1017 55.574 21.1017C53.9358 21.1017 52.5205 20.8291 51.3293 20.285V20.2871Z" fill="#DCA915"/>
                        <path d="M68.283 6.53932H64.5596V3.32422H68.283V6.53932ZM68.283 20.7604H64.6091V8.12116H68.283V20.7604Z" fill="#DCA915"/>
                        <path d="M70.3693 20.7535V8.11418H74.0431V9.69705H74.2413C74.903 8.41142 76.0117 7.76758 77.5673 7.76758C78.3281 7.76758 78.9991 7.95682 79.5782 8.33634C80.1573 8.71586 80.6043 9.25171 80.9192 9.94389H81.1173C81.8781 8.49267 83.0869 7.76758 84.7417 7.76758C86.0485 7.76758 87.0705 8.17178 87.8076 8.97916C88.5436 9.78756 88.9121 10.8922 88.9121 12.293V20.7514H85.2382V13.0839C85.2382 12.4411 85.073 11.9423 84.7417 11.5874C84.4103 11.2326 83.9468 11.0557 83.3512 11.0557C82.7556 11.0557 82.2921 11.2326 81.9607 11.5874C81.6294 11.9423 81.4642 12.4411 81.4642 13.0839V20.7514H77.7903V13.0839C77.7903 12.4411 77.6293 11.9423 77.3062 11.5874C76.9831 11.2326 76.5237 11.0557 75.9281 11.0557C75.3325 11.0557 74.869 11.2326 74.5376 11.5874C74.2062 11.9423 74.0411 12.4411 74.0411 13.0839V20.7514H70.3672L70.3693 20.7535Z" fill="#DCA915"/>
                        <path d="M96.4833 20.5207C95.7721 20.1329 95.2178 19.5848 94.8203 18.8761H94.6221V20.7562H90.9482V3.44238H94.6221V9.99704H94.8203C95.2178 9.27194 95.7721 8.71964 96.4833 8.34012C97.1946 7.9606 98.0142 7.77136 98.9412 7.77136C100.099 7.77136 101.134 8.05625 102.044 8.62501C102.954 9.19378 103.666 9.98161 104.179 10.9875C104.692 11.9934 104.949 13.1391 104.949 14.4258C104.949 15.7124 104.692 16.8623 104.179 17.8764C103.666 18.8905 102.954 19.6814 102.044 20.2512C101.134 20.82 100.099 21.1049 98.9412 21.1049C98.0142 21.1049 97.1946 20.9105 96.4833 20.5238V20.5207ZM100.207 16.8973C100.802 16.2874 101.101 15.4625 101.101 14.4237C101.101 13.3849 100.802 12.5848 100.207 11.9749C99.6111 11.3649 98.8245 11.0595 97.848 11.0595C96.8715 11.0595 96.089 11.3649 95.5016 11.9749C94.9143 12.5848 94.6201 13.4014 94.6201 14.4237C94.6201 15.4461 94.9132 16.2874 95.5016 16.8973C96.089 17.5072 96.8704 17.8126 97.848 17.8126C98.8256 17.8126 99.6101 17.5082 100.207 16.8973Z" fill="#DCA915"/>
                        <path d="M108.846 20.2474C107.936 19.6787 107.225 18.8867 106.711 17.8726C106.198 16.8585 105.941 15.7087 105.941 14.422C105.941 13.1353 106.197 11.9896 106.711 10.9837C107.225 9.97783 107.936 9.19 108.846 8.62124C109.757 8.05247 110.791 7.76758 111.949 7.76758C112.876 7.76758 113.695 7.95682 114.407 8.33634C115.118 8.71586 115.673 9.26817 116.07 9.99326H116.268V8.11316H119.942V20.7524H116.268V18.8723H116.07C115.673 19.581 115.118 20.1302 114.407 20.5169C113.696 20.9036 112.876 21.098 111.949 21.098C110.791 21.098 109.757 20.8131 108.846 20.2444V20.2474ZM115.388 16.8966C115.975 16.2867 116.269 15.4618 116.269 14.423C116.269 13.3842 115.975 12.5841 115.388 11.9742C114.8 11.3643 114.018 11.0588 113.041 11.0588C112.065 11.0588 111.279 11.3643 110.684 11.9742C110.088 12.5841 109.79 13.4007 109.79 14.423C109.79 15.4454 110.088 16.2867 110.684 16.8966C111.279 17.5065 112.065 17.8119 113.041 17.8119C114.018 17.8119 114.799 17.5075 115.388 16.8966Z" fill="#DCA915"/>
                        <path d="M52.0487 42.0531H48.251V24.7393H53.5879L58.3787 35.9438H58.6016L63.3924 24.7393H68.655V42.0531H64.8325V29.8345H64.6095L60.2904 39.8017H56.5918L52.248 29.8345H52.0498V42.0531H52.0487Z" fill="#DCA915"/>
                        <path d="M73.7324 41.5404C72.6815 40.9716 71.8577 40.1797 71.2621 39.1656C70.6665 38.1515 70.3682 37.0016 70.3682 35.715C70.3682 34.4283 70.6665 33.2826 71.2621 32.2767C71.8577 31.2708 72.6805 30.483 73.7324 29.9142C74.7832 29.3454 75.9786 29.0605 77.3195 29.0605C78.6605 29.0605 79.8352 29.3454 80.8943 29.9142C81.9534 30.483 82.7772 31.2708 83.3646 32.2767C83.9519 33.2826 84.2461 34.4283 84.2461 35.715C84.2461 37.0016 83.9519 38.1515 83.3646 39.1656C82.7772 40.1797 81.9534 40.9706 80.8943 41.5404C79.8352 42.1092 78.6439 42.3941 77.3195 42.3941C75.9951 42.3941 74.7832 42.1092 73.7324 41.5404ZM79.541 38.1278C80.0953 37.5262 80.373 36.7219 80.373 35.716C80.373 34.7101 80.0953 33.9069 79.541 33.3042C78.9867 32.7025 78.2455 32.4011 77.3195 32.4011C76.3936 32.4011 75.6483 32.7025 75.0857 33.3042C74.5231 33.9058 74.2413 34.7101 74.2413 35.716C74.2413 36.7219 74.5221 37.5262 75.0857 38.1278C75.6483 38.7295 76.3925 39.0309 77.3195 39.0309C78.2465 39.0309 78.9867 38.7305 79.541 38.1278Z" fill="#DCA915"/>
                        <path d="M85.71 42.0572V29.4179H89.3838V31.1982H89.582C89.9464 30.4896 90.4512 29.9579 91.0964 29.603C91.7416 29.2482 92.4776 29.0713 93.3055 29.0713C94.6464 29.0713 95.7045 29.5002 96.4828 30.3569C97.2601 31.2147 97.6493 32.3851 97.6493 33.8693V42.0561H93.9754V34.8587C93.9754 34.0513 93.7772 33.4321 93.3798 33.0033C92.9824 32.5744 92.4198 32.3604 91.692 32.3604C90.9643 32.3604 90.3965 32.5795 89.9919 33.0156C89.5862 33.4527 89.3838 34.0667 89.3838 34.8587V42.0561H85.71V42.0572Z" fill="#DCA915"/>
                        <path d="M109.156 29.8916C110.174 30.4439 110.968 31.2111 111.54 32.1923C112.11 33.1735 112.396 34.2823 112.396 35.5185C112.396 35.8651 112.372 36.2107 112.322 36.5573H102.715C102.847 37.4315 103.195 38.1114 103.757 38.5979C104.32 39.0843 105.049 39.3271 105.942 39.3271C106.669 39.3271 107.266 39.1707 107.729 38.857C108.193 38.5444 108.482 38.1566 108.599 37.6948H112.198C112.083 38.5855 111.743 39.3847 111.18 40.0943C110.618 40.804 109.872 41.3645 108.947 41.7759C108.02 42.1884 106.969 42.3941 105.794 42.3941C104.486 42.3941 103.315 42.1092 102.281 41.5404C101.247 40.9716 100.44 40.1797 99.8604 39.1656C99.2813 38.1515 98.9912 37.0016 98.9912 35.715C98.9912 34.4283 99.2803 33.3031 99.8604 32.289C100.44 31.2749 101.238 30.483 102.256 29.9142C103.274 29.3454 104.428 29.0605 105.72 29.0605C107.011 29.0605 108.14 29.3372 109.158 29.8895L109.156 29.8916ZM103.906 32.686C103.393 33.0573 103.037 33.5891 102.839 34.2812H108.797C108.581 33.5891 108.214 33.0573 107.692 32.686C107.171 32.3147 106.538 32.1296 105.793 32.1296C105.048 32.1296 104.419 32.3147 103.906 32.686Z" fill="#DCA915"/>
                        <path d="M122.252 29.4131H126L121.234 43.8327C120.837 45.0361 120.348 45.865 119.769 46.3186C119.19 46.7722 118.337 46.9984 117.212 46.9984H114.457V43.808H117.088C117.469 43.808 117.738 43.7463 117.896 43.6229C118.052 43.4995 118.181 43.2639 118.281 42.9184L118.553 42.0524H116.964L112.322 29.4131H116.219L119.322 38.6388H119.545L122.251 29.4131H122.252Z" fill="#DCA915"/>
                        <path d="M10.5107 43.7647C10.5107 43.7647 15.8755 41.0823 14.2321 32.1426C14.2321 32.1426 18.8629 36.3378 17.7584 44.6584C17.7584 44.6584 11.8919 44.3838 10.5107 43.7647Z" fill="#DCA915"/>
                        <path d="M25.3129 43.6268C25.3129 43.6268 22.5825 34.7025 15.9873 30.209C15.9873 30.209 22.7363 30.3921 30.9884 40.3254C30.9884 40.3254 28.1352 42.6179 25.3129 43.6258V43.6268Z" fill="#DCA915"/>
                        <path d="M34.5535 37.2425C34.5535 37.2425 29.2621 29.54 17.7119 26.3311C17.7119 26.3311 28.2494 26.3773 38.4649 31.7873C38.4649 31.7873 36.1185 35.8673 34.5535 37.2435V37.2425Z" fill="#DCA915"/>
                        <path d="M39.4323 26.6912C39.4323 26.6912 33.4967 21.8315 18.9561 23.436C18.9561 23.436 25.6277 17.2465 39.8009 20.731C39.8009 20.731 40.3986 23.436 39.4323 26.6912Z" fill="#DCA915"/>
                        <path d="M36.3621 11.4043C25.4106 10.4416 19.0146 14.6132 19.0146 14.6132C22.9724 8.88242 33.1878 6.86553 33.1878 6.86553C32.1297 5.94913 29.323 3.88595 29.323 3.88595C23.8013 4.66556 16.8985 10.0292 16.8985 10.0292C19.3832 4.84863 25.3652 1.59444 25.3652 1.59444L20.7633 0.768555C14.3673 3.79441 12.4349 9.15806 12.5268 9.61677C12.6197 10.0755 13.6313 10.7173 13.6313 10.7173C12.2346 10.6041 10.2908 11.5987 9.01289 12.3721C8.19222 12.8689 7.67815 13.7431 7.64615 14.6986L7.60279 15.9894C3.41587 18.8322 -0.000976562 23.589 -0.000976562 23.589H2.31959C2.75418 23.589 3.18464 23.5005 3.58413 23.3298L4.7506 22.83C3.2827 24.1855 2.43727 25.4547 1.96655 26.3454C1.49583 27.2371 1.60525 28.3283 2.26281 29.0936C4.22414 31.3758 7.70292 30.0521 7.70292 30.0521C11.5987 27.8059 7.55737 25.6275 7.55737 25.6275L12.6424 22.1584C12.6321 22.1676 12.6217 22.1779 12.6114 22.1872C11.702 23.0532 12.6207 24.5517 13.8068 24.1331C13.813 24.131 13.8202 24.1279 13.8274 24.1259C14.0793 24.0333 14.2982 23.8688 14.4675 23.661C23.6981 12.3403 39.1699 15.687 39.1699 15.687C38.4339 13.2762 36.3631 11.4033 36.3631 11.4033L36.3621 11.4043ZM15.859 14.9341C15.7723 15.1944 15.6215 15.4278 15.4244 15.6191C14.7142 16.3082 12.5412 18.1081 9.12231 18.2182C9.12231 18.2182 9.39483 16.8605 10.2671 15.83C10.7605 15.2468 11.4924 14.9177 12.2584 14.9177H15.8641C15.8641 14.9177 15.8621 14.9228 15.859 14.9331V14.9341Z" fill="#DCA915"/>
                        <path d="M8.42358 35.0922C8.21609 33.6667 7.43362 32.7781 6.21553 32.4428C4.80957 32.0561 3.41186 31.6437 1.98835 31.2354C1.85209 33.0003 2.62836 34.6109 3.34476 36.3357C4.96544 35.5952 6.54483 34.7189 8.42254 35.0922H8.42358Z" fill="#DCA915"/>
                    </svg>

{{--                    Merchant Portal--}}
                </a>
                <!-- Update the navigation section -->
                <div class="hidden md:flex space-x-1">
                    <a href="#" class="text-yellow-500 px-4 py-2 rounded-md text-sm font-medium bg-yellow-500/10">
                        <i class="fas fa-th-large mr-2"></i>Dashboard
                    </a>
                    <a href="https://www.apidog.com/apidoc/shared-54a0de0c-939a-45d8-b512-43f238a6c9f9" target="_blank" class="text-gray-300 hover:text-yellow-500 hover:bg-yellow-500/5 px-4 py-2 rounded-md text-sm font-medium transition-all duration-200">
                        <i class="fas fa-book mr-2"></i>API Docs
                    </a>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-gray-300 flex items-center">
                    <div class="w-8 h-8 rounded-full bg-yellow-500/10 flex items-center justify-center">
                        <i class="fas fa-user text-yellow-500"></i>
                    </div>
                    <span class="ml-2">{{ auth()->user()->name }}</span>
                </div>
                <form action="{{ route('merchant.logout') }}" method="POST">
                    @csrf
                    <button class="text-gray-400 hover:text-yellow-500 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<div class="container mx-auto px-4 py-8">
    <!-- Quick Stats -->
{{--    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">--}}
{{--        <!-- Total Transactions -->--}}
{{--        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 hover:border-yellow-500/30 transition-all duration-300 transform hover:-translate-y-1 stats-gradient">--}}
{{--            <div class="flex items-center">--}}
{{--                <div class="p-3 rounded-full bg-yellow-500/10 text-yellow-500 mr-4">--}}
{{--                    <i class="fas fa-chart-line text-xl"></i>--}}
{{--                </div>--}}
{{--                <div>--}}
{{--                    <p class="text-sm text-gray-400 mb-1">Total Transactions</p>--}}
{{--                    <p class="text-2xl font-bold text-white">1,234</p>--}}
{{--                    <p class="text-xs text-yellow-500 mt-1">--}}
{{--                        <i class="fas fa-arrow-up mr-1"></i>--}}
{{--                        12% from last month--}}
{{--                    </p>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <!-- Total Revenue -->--}}
{{--        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 hover:border-yellow-500/30 transition-all duration-300 transform hover:-translate-y-1 stats-gradient">--}}
{{--            <div class="flex items-center">--}}
{{--                <div class="p-3 rounded-full bg-yellow-500/10 text-yellow-500 mr-4">--}}
{{--                    <i class="fas fa-dollar-sign text-xl"></i>--}}
{{--                </div>--}}
{{--                <div>--}}
{{--                    <p class="text-sm text-gray-400 mb-1">Total Revenue</p>--}}
{{--                    <p class="text-2xl font-bold text-white">$45,678</p>--}}
{{--                    <p class="text-xs text-yellow-500 mt-1">--}}
{{--                        <i class="fas fa-arrow-up mr-1"></i>--}}
{{--                        8% from last month--}}
{{--                    </p>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <!-- Success Rate -->--}}
{{--        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 hover:border-yellow-500/30 transition-all duration-300 transform hover:-translate-y-1 stats-gradient">--}}
{{--            <div class="flex items-center">--}}
{{--                <div class="p-3 rounded-full bg-yellow-500/10 text-yellow-500 mr-4">--}}
{{--                    <i class="fas fa-check-circle text-xl"></i>--}}
{{--                </div>--}}
{{--                <div>--}}
{{--                    <p class="text-sm text-gray-400 mb-1">Success Rate</p>--}}
{{--                    <p class="text-2xl font-bold text-white">98.5%</p>--}}
{{--                    <p class="text-xs text-yellow-500 mt-1">--}}
{{--                        <i class="fas fa-arrow-up mr-1"></i>--}}
{{--                        2% from last month--}}
{{--                    </p>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <!-- Active Users -->--}}
{{--        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 hover:border-yellow-500/30 transition-all duration-300 transform hover:-translate-y-1 stats-gradient">--}}
{{--            <div class="flex items-center">--}}
{{--                <div class="p-3 rounded-full bg-yellow-500/10 text-yellow-500 mr-4">--}}
{{--                    <i class="fas fa-users text-xl"></i>--}}
{{--                </div>--}}
{{--                <div>--}}
{{--                    <p class="text-sm text-gray-400 mb-1">Active Customers</p>--}}
{{--                    <p class="text-2xl font-bold text-white">892</p>--}}
{{--                    <p class="text-xs text-yellow-500 mt-1">--}}
{{--                        <i class="fas fa-arrow-up mr-1"></i>--}}
{{--                        15% from last month--}}
{{--                    </p>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

    <!-- Main Content -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Merchant Profile -->
        <div class="col-span-2">
            <div class="bg-gray-800 rounded-lg shadow-2xl border border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700 bg-gradient-to-r from-gray-800 to-gray-900">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <i class="fas fa-store text-yellow-500 mr-3"></i>
                            <h4 class="text-xl font-bold text-white">Merchant Profile</h4>
                        </div>
                        <span class="px-4 py-1.5 rounded-full text-sm font-medium inline-flex items-center
                                {{ $merchant->status === 'ACTIVE' ? 'bg-yellow-500/10 text-yellow-500' : 'bg-gray-700 text-gray-300' }}">
                                <i class="fas fa-circle text-xs mr-2"></i>
                                {{ $merchant->status }}
                            </span>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Business Information -->
                    <div class="space-y-4">
                        <h5 class="text-lg font-semibold text-white flex items-center">
                            <i class="fas fa-building text-yellow-500 mr-2"></i>
                            Business Information
                        </h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                                <p class="text-gray-400 text-sm mb-1">Business Name</p>
                                <p class="text-white font-medium">{{ $merchant->business_name }}</p>
                            </div>
                            <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                                <p class="text-gray-400 text-sm mb-1">Merchant Code</p>
                                <p class="text-white font-medium">{{ $merchant->merchant_code }}</p>
                            </div>
                            <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                                <p class="text-gray-400 text-sm mb-1">Notification Email</p>
                                <p class="text-white font-medium">{{ $merchant->notification_email }}</p>
                            </div>
                            <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                                <p class="text-gray-400 text-sm mb-1">Registration Date</p>
                                <p class="text-white font-medium">{{ $merchant->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Callback URL -->
                    <div class="space-y-4">
                        <h5 class="text-lg font-semibold text-white flex items-center">
                            <i class="fas fa-link text-yellow-500 mr-2"></i>
                            Callback URL
                        </h5>
                        <div class="bg-gray-700/50 rounded-lg p-4 border border-gray-600">
                            <p class="text-white font-medium break-all">
                                {{ $merchant->callback_url ?: 'Not configured' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Credentials -->
        <div class="col-span-1">
            <div class="bg-gray-800 rounded-lg shadow-2xl border border-gray-700">
                <div class="px-6 py-4 border-b border-gray-700 bg-gradient-to-r from-gray-800 to-gray-900">
                    <h5 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-key text-yellow-500 mr-2"></i>
                        API Credentials
                    </h5>
                </div>

                <div class="p-6 space-y-6">
                    @if($merchant->api_key)
                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-300">API Key</label>
                            <div class="relative">
                                <input type="password" value="{{ $merchant->api_key }}" readonly id="apiKey"
                                       class="w-full px-4 py-2 rounded-md border border-gray-600 bg-gray-700 text-white font-mono text-sm focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500" />
                                <div class="absolute right-0 top-0 h-full flex">
                                    <button onclick="toggleApiKey()"
                                            class="px-3 flex items-center justify-center text-gray-300 hover:text-yellow-500 transition-colors">
                                        <i class="far fa-eye"></i>
                                    </button>
                                    <button onclick="copyApiKey()"
                                            class="px-3 flex items-center justify-center text-gray-300 hover:text-yellow-500 transition-colors">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-400 flex items-center">
                                <i class="far fa-clock mr-1"></i>
                                Generated: {{ $merchant->api_key_generated_at->diffForHumans() }}
                            </p>
                        </div>
                    @endif

                    @if($merchant->status === 'ACTIVE')
                        <form action="{{ route('merchant.generate-api-key') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center justify-center px-4 py-2 rounded-md text-gray-900 bg-yellow-500 hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 font-medium transition-all duration-200">
                                <i class="fas fa-sync-alt mr-2"></i>
                                {{ $merchant->api_key ? 'Regenerate API Key' : 'Generate API Key' }}
                            </button>
                        </form>
                    @else
                        <div class="bg-red-900/50 rounded-md p-4 border border-red-800">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-red-400 mt-1"></i>
                                <div class="ml-3">
                                    <p class="text-sm text-red-300">
                                        API key generation is only available for active merchants. Please contact support for activation.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 gap-3">

                        <a href="#" class="flex items-center p-3 rounded-lg bg-gray-700/50 border border-gray-600 hover:border-yellow-500/30 transition-all duration-200 group">
                            <div class="p-2 rounded-full bg-yellow-500/10 text-yellow-500 mr-3">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <h6 class="text-white font-medium group-hover:text-yellow-500 transition-colors">View Reports</h6>
                                <p class="text-sm text-gray-400">Access transaction reports</p>
                            </div>
                        </a>

                        <a href="#" class="flex items-center p-3 rounded-lg bg-gray-700/50 border border-gray-600 hover:border-yellow-500/30 transition-all duration-200 group">
                            <div class="p-2 rounded-full bg-yellow-500/10 text-yellow-500 mr-3">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div>
                                <h6 class="text-white font-medium group-hover:text-yellow-500 transition-colors">API Settings</h6>
                                <p class="text-sm text-gray-400">Configure API preferences</p>
                            </div>
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
<script>
    // Show notification function
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed bottom-4 right-4 bg-yellow-500 text-gray-900 px-6 py-3 rounded-md shadow-lg font-medium opacity-0 transition-opacity duration-300';
        notification.innerHTML = `<i class="fas fa-check-circle mr-2"></i> ${message}`;
        document.body.appendChild(notification);

        // Fade in
        setTimeout(() => {
            notification.classList.remove('opacity-0');
        }, 100);

        // Fade out
        setTimeout(() => {
            notification.classList.add('opacity-0');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Copy API Key function
    function copyApiKey() {
        const apiKeyInput = document.getElementById('apiKey');
        apiKeyInput.type = 'text';
        apiKeyInput.select();
        document.execCommand('copy');
        apiKeyInput.type = 'password';

        showNotification('API key copied to clipboard');
    }

    // Toggle API Key visibility
    function toggleApiKey() {
        const apiKeyInput = document.getElementById('apiKey');
        const eyeIcon = document.querySelector('.fa-eye, .fa-eye-slash');

        if (apiKeyInput.type === 'password') {
            apiKeyInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            apiKeyInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }

    // Copy code snippets
    function copyCode(buttonElement) {
        const codeBlock = buttonElement.closest('.code-block').querySelector('code, pre');
        const textArea = document.createElement('textarea');
        textArea.value = codeBlock.textContent;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);

        showNotification('Code copied to clipboard');
    }

    // Add copy buttons to all code blocks
    document.addEventListener('DOMContentLoaded', function() {
        const codeBlocks = document.querySelectorAll('.code-block');
        codeBlocks.forEach(block => {
            const copyButton = document.createElement('button');
            copyButton.className = 'absolute top-2 right-2 p-2 text-gray-400 hover:text-yellow-500 transition-colors';
            copyButton.innerHTML = '<i class="far fa-copy"></i>';
            copyButton.onclick = function() { copyCode(this); };
            block.style.position = 'relative';
            block.appendChild(copyButton);
        });
    });

    // Smooth scroll to documentation section
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add loading animation when generating API key
    document.querySelector('form[action*="generate-api-key"]')?.addEventListener('submit', function(e) {
        const button = this.querySelector('button[type="submit"]');
        button.disabled = true;
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Generating...
        `;
    });

    // Highlight active navigation item
    function setActiveNavItem() {
        const path = window.location.pathname;
        const navItems = document.querySelectorAll('nav a');
        navItems.forEach(item => {
            if (item.getAttribute('href') === path) {
                item.classList.add('text-yellow-500', 'bg-yellow-500/10');
                item.classList.remove('text-gray-300', 'hover:text-yellow-500');
            }
        });
    }
    setActiveNavItem();
</script>
</body>




</html>

