<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Liy lang pou validasyon
    |--------------------------------------------------------------------------
    |
    | Liy lang sa yo genyen mesaj erè pa defo klas validasyon an itilize.
    | Kèk nan règ sa yo gen plizyè vèsyon, tankou règ gwosè yo. Ou lib
    | pou chanje chak nan mesaj sa yo isit la.
    |
    */

    'accepted' => 'Chan :attribute a dwe aksepte.',
    'accepted_if' => 'Chan :attribute a dwe aksepte lè :other se :value.',
    'active_url' => 'Chan :attribute a dwe yon URL ki valid.',
    'after' => 'Chan :attribute a dwe yon dat ki apre :date.',
    'after_or_equal' => 'Chan :attribute a dwe yon dat ki apre oswa egal ak :date.',
    'alpha' => 'Chan :attribute a dwe gen sèlman lèt.',
    'alpha_dash' => 'Chan :attribute a dwe gen sèlman lèt, chif, tirè ak tras anba.',
    'alpha_num' => 'Chan :attribute a dwe gen sèlman lèt ak chif.',
    'any_of' => 'Chan :attribute a pa valid.',
    'array' => 'Chan :attribute a dwe yon tablo (array).',
    'ascii' => 'Chan :attribute a dwe gen sèlman karaktè alfanimerik ak senbòl yon sèl byte.',
    'before' => 'Chan :attribute a dwe yon dat ki anvan :date.',
    'before_or_equal' => 'Chan :attribute a dwe yon dat ki anvan oswa egal ak :date.',
    'between' => [
        'array' => 'Chan :attribute a dwe gen ant :min ak :max eleman.',
        'file' => 'Chan :attribute a dwe ant :min ak :max kilo-oktè.',
        'numeric' => 'Chan :attribute a dwe ant :min ak :max.',
        'string' => 'Chan :attribute a dwe gen ant :min ak :max karaktè.',
    ],
    'boolean' => 'Chan :attribute a dwe vre oswa fo.',
    'can' => 'Chan :attribute a gen yon valè ki pa otorize.',
    'confirmed' => 'Konfimasyon chan :attribute a pa koresponn.',
    'contains' => 'Chan :attribute a manke yon valè obligatwa.',
    'current_password' => 'Modpas la pa kòrèk.',
    'date' => 'Chan :attribute a dwe yon dat ki valid.',
    'date_equals' => 'Chan :attribute a dwe yon dat ki egal ak :date.',
    'date_format' => 'Chan :attribute a dwe koresponn ak fòma :format.',
    'decimal' => 'Chan :attribute a dwe gen :decimal chif desimal.',
    'declined' => 'Chan :attribute a dwe refize.',
    'declined_if' => 'Chan :attribute a dwe refize lè :other se :value.',
    'different' => 'Chan :attribute ak :other dwe diferan.',
    'digits' => 'Chan :attribute a dwe gen :digits chif.',
    'digits_between' => 'Chan :attribute a dwe gen ant :min ak :max chif.',
    'dimensions' => 'Chan :attribute a gen dimansyon imaj ki pa valid.',
    'distinct' => 'Chan :attribute a gen yon valè an doub.',
    'doesnt_contain' => 'Chan :attribute a pa dwe genyen okenn nan valè sa yo: :values.',
    'doesnt_end_with' => 'Chan :attribute a pa dwe fini ak youn nan valè sa yo: :values.',
    'doesnt_start_with' => 'Chan :attribute a pa dwe kòmanse ak youn nan valè sa yo: :values.',
    'email' => 'Chan :attribute a dwe yon adrès imèl ki valid.',
    'encoding' => 'Chan :attribute a dwe kode nan :encoding.',
    'ends_with' => 'Chan :attribute a dwe fini ak youn nan valè sa yo: :values.',
    'enum' => ':attribute ou chwazi a pa valid.',
    'exists' => ':attribute ou chwazi a pa valid.',
    'extensions' => 'Chan :attribute a dwe gen youn nan ekstansyon sa yo: :values.',
    'file' => 'Chan :attribute a dwe yon fichye.',
    'filled' => 'Chan :attribute a dwe gen yon valè.',
    'gt' => [
        'array' => 'Chan :attribute a dwe gen plis pase :value eleman.',
        'file' => 'Chan :attribute a dwe pi gwo pase :value kilo-oktè.',
        'numeric' => 'Chan :attribute a dwe pi gwo pase :value.',
        'string' => 'Chan :attribute a dwe gen plis pase :value karaktè.',
    ],
    'gte' => [
        'array' => 'Chan :attribute a dwe gen :value eleman oswa plis.',
        'file' => 'Chan :attribute a dwe pi gwo pase oswa egal ak :value kilo-oktè.',
        'numeric' => 'Chan :attribute a dwe pi gwo pase oswa egal ak :value.',
        'string' => 'Chan :attribute a dwe gen omwen :value karaktè.',
    ],
    'hex_color' => 'Chan :attribute a dwe yon koulè eksadesimal ki valid.',
    'image' => 'Chan :attribute a dwe yon imaj.',
    'in' => ':attribute ou chwazi a pa valid.',
    'in_array' => 'Chan :attribute a dwe egziste nan :other.',
    'in_array_keys' => 'Chan :attribute a dwe gen omwen youn nan kle sa yo: :values.',
    'integer' => 'Chan :attribute a dwe yon nonm antye.',
    'ip' => 'Chan :attribute a dwe yon adrès IP ki valid.',
    'ipv4' => 'Chan :attribute a dwe yon adrès IPv4 ki valid.',
    'ipv6' => 'Chan :attribute a dwe yon adrès IPv6 ki valid.',
    'json' => 'Chan :attribute a dwe yon chèn JSON ki valid.',
    'list' => 'Chan :attribute a dwe yon lis.',
    'lowercase' => 'Chan :attribute a dwe an miniskil.',
    'lt' => [
        'array' => 'Chan :attribute a dwe gen mwens pase :value eleman.',
        'file' => 'Chan :attribute a dwe pi piti pase :value kilo-oktè.',
        'numeric' => 'Chan :attribute a dwe pi piti pase :value.',
        'string' => 'Chan :attribute a dwe gen mwens pase :value karaktè.',
    ],
    'lte' => [
        'array' => 'Chan :attribute a pa dwe gen plis pase :value eleman.',
        'file' => 'Chan :attribute a dwe pi piti pase oswa egal ak :value kilo-oktè.',
        'numeric' => 'Chan :attribute a dwe pi piti pase oswa egal ak :value.',
        'string' => 'Chan :attribute a pa dwe gen plis pase :value karaktè.',
    ],
    'mac_address' => 'Chan :attribute a dwe yon adrès MAC ki valid.',
    'max' => [
        'array' => 'Chan :attribute a pa dwe gen plis pase :max eleman.',
        'file' => 'Chan :attribute a pa dwe depase :max kilo-oktè.',
        'numeric' => 'Chan :attribute a pa dwe pi gwo pase :max.',
        'string' => 'Chan :attribute a pa dwe depase :max karaktè.',
    ],
    'max_digits' => 'Chan :attribute a pa dwe gen plis pase :max chif.',
    'mimes' => 'Chan :attribute a dwe yon fichye ki gen tip: :values.',
    'mimetypes' => 'Chan :attribute a dwe yon fichye ki gen tip: :values.',
    'min' => [
        'array' => 'Chan :attribute a dwe gen omwen :min eleman.',
        'file' => 'Chan :attribute a dwe omwen :min kilo-oktè.',
        'numeric' => 'Chan :attribute a dwe omwen :min.',
        'string' => 'Chan :attribute a dwe gen omwen :min karaktè.',
    ],
    'min_digits' => 'Chan :attribute a dwe gen omwen :min chif.',
    'missing' => 'Chan :attribute a dwe manke.',
    'missing_if' => 'Chan :attribute a dwe manke lè :other se :value.',
    'missing_unless' => 'Chan :attribute a dwe manke sof si :other se :value.',
    'missing_with' => 'Chan :attribute a dwe manke lè :values prezan.',
    'missing_with_all' => 'Chan :attribute a dwe manke lè :values yo prezan.',
    'multiple_of' => 'Chan :attribute a dwe yon miltip de :value.',
    'not_in' => ':attribute ou chwazi a pa valid.',
    'not_regex' => 'Fòma chan :attribute a pa valid.',
    'numeric' => 'Chan :attribute a dwe yon nonm.',
    'password' => [
        'letters' => 'Chan :attribute a dwe gen omwen yon lèt.',
        'mixed' => 'Chan :attribute a dwe gen omwen yon majiskil ak yon miniskil.',
        'numbers' => 'Chan :attribute a dwe gen omwen yon chif.',
        'symbols' => 'Chan :attribute a dwe gen omwen yon senbòl.',
        'uncompromised' => ':attribute yo bay la parèt nan yon fuit done. Tanpri chwazi yon lòt :attribute.',
    ],
    'present' => 'Chan :attribute a dwe prezan.',
    'present_if' => 'Chan :attribute a dwe prezan lè :other se :value.',
    'present_unless' => 'Chan :attribute a dwe prezan sof si :other se :value.',
    'present_with' => 'Chan :attribute a dwe prezan lè :values prezan.',
    'present_with_all' => 'Chan :attribute a dwe prezan lè :values yo prezan.',
    'prohibited' => 'Chan :attribute a entèdi.',
    'prohibited_if' => 'Chan :attribute a entèdi lè :other se :value.',
    'prohibited_if_accepted' => 'Chan :attribute a entèdi lè :other aksepte.',
    'prohibited_if_declined' => 'Chan :attribute a entèdi lè :other refize.',
    'prohibited_unless' => 'Chan :attribute a entèdi sof si :other nan :values.',
    'prohibits' => 'Chan :attribute a entèdi :other prezan.',
    'regex' => 'Fòma chan :attribute a pa valid.',
    'required' => 'Chan :attribute a obligatwa.',
    'required_array_keys' => 'Chan :attribute a dwe gen antre pou: :values.',
    'required_if' => 'Chan :attribute a obligatwa lè :other se :value.',
    'required_if_accepted' => 'Chan :attribute a obligatwa lè :other aksepte.',
    'required_if_declined' => 'Chan :attribute a obligatwa lè :other refize.',
    'required_unless' => 'Chan :attribute a obligatwa sof si :other nan :values.',
    'required_with' => 'Chan :attribute a obligatwa lè :values prezan.',
    'required_with_all' => 'Chan :attribute a obligatwa lè :values yo prezan.',
    'required_without' => 'Chan :attribute a obligatwa lè :values pa prezan.',
    'required_without_all' => 'Chan :attribute a obligatwa lè okenn nan :values pa prezan.',
    'same' => 'Chan :attribute a dwe koresponn ak :other.',
    'size' => [
        'array' => 'Chan :attribute a dwe gen :size eleman.',
        'file' => 'Chan :attribute a dwe :size kilo-oktè.',
        'numeric' => 'Chan :attribute a dwe :size.',
        'string' => 'Chan :attribute a dwe gen :size karaktè.',
    ],
    'starts_with' => 'Chan :attribute a dwe kòmanse ak youn nan valè sa yo: :values.',
    'string' => 'Chan :attribute a dwe yon chèn karaktè.',
    'timezone' => 'Chan :attribute a dwe yon fuizo orè ki valid.',
    'unique' => 'Valè chan :attribute a deja itilize.',
    'uploaded' => 'Telechajman chan :attribute a echwe.',
    'uppercase' => 'Chan :attribute a dwe an majiskil.',
    'url' => 'Chan :attribute a dwe yon URL ki valid.',
    'ulid' => 'Chan :attribute a dwe yon ULID ki valid.',
    'uuid' => 'Chan :attribute a dwe yon UUID ki valid.',

    /*
    |--------------------------------------------------------------------------
    | Liy lang pou validasyon pèsonalize
    |--------------------------------------------------------------------------
    |
    | Ou ka presize isit la mesaj validasyon pèsonalize pou atribi lè w
    | itilize konvansyon "atribi.règ" pou nome liy yo. Sa fè li fasil pou
    | presize yon liy lang pèsonalize pou yon règ atribi espesifik.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'mesaj-pèsonalize',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Atribi validasyon pèsonalize
    |--------------------------------------------------------------------------
    |
    | Liy lang sa yo itilize pou ranplase espas rezève atribi nou an ak
    | yon bagay pi fasil pou li konprann, tankou "Adrès imèl" olye de
    | "email". Sa senpleman ede nou fè mesaj nou yo pi klè.
    |
    */

    'attributes' => [],

];
