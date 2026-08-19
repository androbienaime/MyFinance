<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LT FINANCE — Votre espace en ligne arrive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,500&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #FFFDF8;
            --ink: #1C2333;
            --blue: #1E4396;
            --blue-deep: #14306E;
            --yellow: #F4B429;
            --yellow-soft: #FCE7B4;
            --grey: #6B7182;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Work Sans', sans-serif;
            background: var(--paper);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .page {
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            align-items: center;
            gap: 56px;
        }

        /* Colonne texte */
        .content { max-width: 460px; }

        .mark {
            display: inline-flex;
            align-items: baseline;
            gap: 2px;
            font-family: 'Lora', serif;
            font-weight: 600;
            font-size: 22px;
            color: var(--blue-deep);
            margin-bottom: 40px;
        }
        .mark .dot { color: var(--yellow); font-size: 26px; line-height: 0; }

        h1 {
            font-family: 'Lora', serif;
            font-weight: 500;
            font-style: italic;
            font-size: clamp(1.9rem, 3.4vw, 2.5rem);
            line-height: 1.28;
            color: var(--ink);
            margin-bottom: 22px;
        }

        h1 .hl {
            position: relative;
            font-style: normal;
            font-weight: 600;
            color: var(--blue-deep);
        }

        h1 .hl svg {
            position: absolute;
            left: -2%;
            bottom: -6px;
            width: 104%;
            height: 10px;
        }

        p.lead {
            font-size: 16.5px;
            line-height: 1.7;
            color: var(--grey);
            margin-bottom: 30px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--yellow-soft);
            border-radius: 999px;
            padding: 9px 18px 9px 14px;
            margin-bottom: 34px;
        }

        .status .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--blue);
            position: relative;
            flex-shrink: 0;
        }
        .status .pulse-dot::after {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            border: 1.5px solid var(--blue);
            opacity: 0.5;
            animation: ring 2.2s ease-out infinite;
        }
        @keyframes ring {
            0%   { transform: scale(0.6); opacity: 0.6; }
            100% { transform: scale(1.9); opacity: 0; }
        }

        .status span {
            font-size: 13px;
            font-weight: 500;
            color: var(--blue-deep);
            letter-spacing: 0.01em;
        }

        .note {
            font-size: 14px;
            color: var(--grey);
            border-left: 2px solid var(--yellow);
            padding-left: 14px;
        }

        .note strong { color: var(--ink); font-weight: 600; }

        /* Colonne illustration — tracé fait main, pas de badge géométrique parfait */
        .art {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .art svg { width: 100%; max-width: 380px; height: auto; }

        .caption {
            position: absolute;
            bottom: -6px;
            right: 8%;
            font-family: 'Lora', serif;
            font-style: italic;
            font-size: 13px;
            color: var(--grey);
        }

        @media (max-width: 780px) {
            .page {
                grid-template-columns: 1fr;
                gap: 40px;
                text-align: left;
            }
            .art { order: -1; }
            .art svg { max-width: 260px; }
            .content { max-width: 100%; }
        }

        @media (prefers-reduced-motion: reduce) {
            .pulse-dot::after { animation: none; }
        }
    </style>
</head>
<body>

    <div class="page">
        <div class="content">
            <div class="mark">LT FINANCE<span class="dot">.</span></div>

            <h1>
                Votre espace client passe
                <span class="hl">en ligne<svg viewBox="0 0 140 10" preserveAspectRatio="none"><path d="M2 7.5C22 3.2 45 2 68 4.8C91 7.6 112 4.5 138 2.5" fill="none" stroke="#F4B429" stroke-width="4" stroke-linecap="round"/></svg></span>,
                en ce moment même.
            </h1>

            <p class="lead">
                Nous construisons la version web de LT FINANCE — consulter vos comptes, suivre vos opérations
                et gérer vos transferts, sans passer par une agence. C'est du travail d'artisan, on préfère
                bien faire que faire vite.
            </p>

            <div class="status">
                <span class="pulse-dot"></span>
                <span>En développement</span>
            </div>

            <p class="note"><strong>D'ici là,</strong> votre agence habituelle reste ouverte comme toujours pour toutes vos opérations.</p>
        </div>

        <div class="art">
            <!-- Illustration tracée à la main : une courbe de croissance simple, imparfaite, humaine -->
            <svg viewBox="0 0 380 340" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- socle -->
                <path d="M40 300 L340 300" stroke="#E7E2D4" stroke-width="1.5" stroke-linecap="round"/>

                <!-- barres, hauteurs et largeurs légèrement irrégulières -->
                <rect x="66" y="240" width="34" height="60" rx="3" fill="#FCE7B4"/>
                <rect x="128" y="196" width="34" height="104" rx="3" fill="#FCE7B4"/>
                <rect x="190" y="150" width="34" height="150" rx="3" fill="#F4B429"/>
                <rect x="252" y="118" width="34" height="182" rx="3" fill="#1E4396"/>

                <!-- courbe tracée à main levée, légèrement irrégulière -->
                <path d="M60 252 C100 218, 118 236, 148 190 C176 148, 192 168, 226 130 C256 98, 268 116, 298 78"
                      stroke="#14306E" stroke-width="3" stroke-linecap="round" fill="none"/>

                <!-- petit point d'arrivée, comme une note manuscrite -->
                <circle cx="298" cy="78" r="6" fill="#F4B429" stroke="#14306E" stroke-width="2"/>

                <!-- flèche esquissée à la main -->
                <path d="M283 68 L299 76 L289 92" stroke="#14306E" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" fill="none" transform="rotate(-8 298 78)"/>

                <!-- annotation légère, façon note au crayon -->
                <text x="252" y="60" font-family="Lora, serif" font-style="italic" font-size="14" fill="#6B7182">presque là</text>
            </svg>
        </div>
    </div>

</body>
</html>