<section class="pm-login-page">

    <!-- =========================================================
         DECORATIVE BACKGROUND
         ========================================================= -->
    <div
        class="pm-login-background"
        aria-hidden="true"
    >
        <span class="pm-login-bg-orb pm-login-bg-orb-blue"></span>
        <span class="pm-login-bg-orb pm-login-bg-orb-orange"></span>
        <span class="pm-login-bg-orb pm-login-bg-orb-cyan"></span>

        <span class="pm-login-bg-line pm-login-bg-line-one"></span>
        <span class="pm-login-bg-line pm-login-bg-line-two"></span>
    </div>


    <!-- =========================================================
         LEFT / BRAND AREA
         ========================================================= -->
    <section class="pm-login-hero">

        <!-- Brand -->
        <header class="pm-login-brand">

            <img
                src="/assets/images/penamas-icon.png"
                alt="Logo PENA MAS"
            >

            <div>

                <strong>
                    PENA MAS
                </strong>

                <small>
                    Penomoran Agenda dan Manajemen Arsip Surat
                </small>

                <span class="pm-login-agency">
                    BADAN PUSAT STATISTIK KOTA PALANGKA RAYA
                </span>

            </div>

        </header>


        <!-- Main copy -->
        <div class="pm-login-copy">

            <h1>
                Administrasi surat
                <br>
                yang

                <span class="pm-login-gradient-text">
                    lebih cepat
                </span>

                <br>

                dan

                <span class="pm-login-orange-text">
                    tertib.
                </span>
            </h1>

            <p>
                PENA MAS membantu proses penomoran,
                pencatatan agenda, pengelolaan arsip,
                dan pelacakan surat dalam satu sistem
                terintegrasi.
            </p>

        </div>


        <!-- =====================================================
             ANIMATED VECTOR SCENE
             Tidak memakai CDN / internet.
             ===================================================== -->
        <div
            class="pm-login-scene-wrap"
            aria-hidden="true"
        >

            <div
                class="pm-login-scene"
                id="pmLoginVisual"
            >

                <!-- Ambient light -->
                <div class="pm-scene-aura"></div>


                <!-- Orbit rings -->
                <div class="pm-orbit pm-orbit-one"></div>
                <div class="pm-orbit pm-orbit-two"></div>
                <div class="pm-orbit pm-orbit-three"></div>


                <!-- Small stars -->
                <span class="pm-star pm-star-1"></span>
                <span class="pm-star pm-star-2"></span>
                <span class="pm-star pm-star-3"></span>
                <span class="pm-star pm-star-4"></span>
                <span class="pm-star pm-star-5"></span>
                <span class="pm-star pm-star-6"></span>


                <!-- Floating left icon -->
                <div class="pm-floating-card pm-floating-card-left">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M6 2h9l5 5v15H6z"/>
                        <path d="M14 2v6h6"/>
                        <path d="M9 13h6"/>
                        <path d="M9 17h4"/>
                    </svg>

                </div>


                <!-- Floating top icon -->
                <div class="pm-floating-card pm-floating-card-top">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 12h4l2-7 4 14 2-7h6"/>
                    </svg>

                </div>


                <!-- Floating right icon -->
                <div class="pm-floating-card pm-floating-card-right">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>

                </div>


                <!-- Rear document -->
                <div class="pm-rear-document">

                    <span></span>
                    <span></span>
                    <span></span>

                </div>


                <!-- Folder -->
                <div class="pm-hero-folder">

                    <div class="pm-folder-tab"></div>

                    <div class="pm-folder-face">

                        <img
                            src="/assets/images/penamas-icon.png"
                            alt=""
                        >

                    </div>

                </div>


                <!-- Main document -->
                <div class="pm-main-document">

                    <div class="pm-document-corner"></div>

                    <span class="pm-document-line pm-document-line-1"></span>
                    <span class="pm-document-line pm-document-line-2"></span>
                    <span class="pm-document-line pm-document-line-3"></span>
                    <span class="pm-document-line pm-document-line-4"></span>

                </div>


                <!-- Pen -->
                <div class="pm-animated-pen">

                    <span class="pm-pen-cap"></span>
                    <span class="pm-pen-body"></span>
                    <span class="pm-pen-tip"></span>

                </div>


                <!-- Pedestal -->
                <div class="pm-scene-pedestal">

                    <span class="pm-pedestal-top"></span>
                    <span class="pm-pedestal-base"></span>

                </div>

            </div>

        </div>


        <!-- =====================================================
             FEATURE CARDS
             ===================================================== -->
        <div class="pm-login-features">

            <article class="pm-login-feature">

                <span class="pm-feature-icon pm-feature-blue">

                    <?= ui_icon('shield') ?>

                </span>

                <div>

                    <strong>
                        Aman & Tercatat
                    </strong>

                    <small>
                        Setiap surat tersimpan secara sistematis.
                    </small>

                </div>

            </article>


            <article class="pm-login-feature">

                <span class="pm-feature-icon pm-feature-orange">

                    <?= ui_icon('file') ?>

                </span>

                <div>

                    <strong>
                        Penomoran Otomatis
                    </strong>

                    <small>
                        Nomor surat terbentuk dengan cepat dan akurat.
                    </small>

                </div>

            </article>


            <article class="pm-login-feature">

                <span class="pm-feature-icon pm-feature-green">

                    <?= ui_icon('archive') ?>

                </span>

                <div>

                    <strong>
                        Arsip Terkelola
                    </strong>

                    <small>
                        Agenda dan arsip mudah dicari dan dipantau.
                    </small>

                </div>

            </article>

        </div>

    </section>


    <!-- =========================================================
         RIGHT / LOGIN AREA
         ========================================================= -->
    <section class="pm-login-auth">

        <!-- Mobile branding -->
        <div class="pm-login-mobile-brand">

            <img
                src="/assets/images/penamas-icon.png"
                alt="Logo PENA MAS"
            >

            <div>

                <strong>
                    PENA MAS
                </strong>

                <small>
                    Manajemen Arsip Surat
                </small>

            </div>

        </div>


        <div
            class="pm-login-card"
            id="pmLoginCard"
        >

            <!-- moving glow -->
            <div
                class="pm-login-card-glow"
                aria-hidden="true"
            ></div>


            <!-- =================================================
                 USER ICON INSIDE LOGIN CARD
                 Hanya bagian ini yang berubah.
                 ================================================= -->
            <div class="pm-login-card-brand">

                <span
                    aria-hidden="true"
                    style="
                        width: 43px;
                        height: 49px;
                        flex: 0 0 43px;

                        display: grid;
                        place-items: center;

                        border-radius: 12px;

                        color: #37c7ff;

                        background:
                            linear-gradient(
                                145deg,
                                rgba(20, 126, 190, .22),
                                rgba(6, 74, 126, .12)
                            );

                        border:
                            1px solid
                            rgba(75, 191, 246, .30);

                        box-shadow:
                            inset 0 1px 0
                            rgba(255, 255, 255, .08),
                            0 8px 20px
                            rgba(0, 115, 190, .10);
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="
                            width: 25px;
                            height: 25px;
                        "
                    >
                        <circle
                            cx="12"
                            cy="8"
                            r="4"
                        />

                        <path
                            d="M4 21a8 8 0 0 1 16 0"
                        />
                    </svg>
                </span>


                <div>

                    <strong>
                        PENA MAS
                    </strong>

                    <small>
                        Penomoran Agenda dan Manajemen Arsip Surat
                    </small>

                </div>

            </div>


            <div class="pm-login-card-divider"></div>


            <div class="pm-login-heading">

                <h2>
                    Selamat datang
                </h2>

                <p>
                    Masuk ke akun Anda untuk melanjutkan
                    ke PENA MAS.
                </p>

            </div>


            <form
                method="post"
                action="/login"
                class="pm-login-form"
                data-loading-form
                autocomplete="off"
            >

                <?= csrf_field() ?>


                <!-- Email -->
                <label class="pm-auth-field">

                    <span>
                        Email
                    </span>

                    <div class="pm-auth-field-shell">

                        <svg
                            class="pm-auth-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M20 21a8 8 0 0 0-16 0"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>

                        <input
                            type="email"
                            name="email"
                            placeholder="nama@bps.go.id"
                            required
                            autofocus
                            autocomplete="username"
                        >

                    </div>

                </label>


                <!-- Password -->
                <label class="pm-auth-field">

                    <span>
                        Password
                    </span>

                    <div class="pm-auth-field-shell">

                        <svg
                            class="pm-auth-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect
                                x="5"
                                y="10"
                                width="14"
                                height="11"
                                rx="2"
                            />

                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>


                        <input
                            id="passwordInput"
                            type="password"
                            name="password"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >


                        <button
                            type="button"
                            id="passwordToggle"
                            class="pm-password-toggle"
                            aria-label="Tampilkan password"
                        >

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>

                        </button>

                    </div>

                </label>


                <!-- Submit -->
                <button
                    class="pm-login-submit"
                    type="submit"
                >

                    <span>
                        Masuk ke PENA MAS
                    </span>


                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>

                </button>

            </form>


            <div class="pm-login-card-footer">

                <span class="pm-secure-indicator">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>

                </span>

                <span>
                    Autentikasi Aman
                </span>

            </div>

        </div>

    </section>

</section>