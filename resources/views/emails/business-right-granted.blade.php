<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>営業権獲得のお知らせ</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #003672;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .highlight {
            background-color: #e8f4fd;
            border-left: 4px solid #003672;
            padding: 15px;
            margin: 20px 0;
        }
        .ranking {
            font-size: 24px;
            font-weight: bold;
            color: #003672;
        }
        .amount {
            font-size: 20px;
            font-weight: bold;
            color: #e74c3c;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .table th {
            background-color: #003672;
            color: white;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏆 営業権獲得のお知らせ</h1>
    </div>
    
    <div class="content">
        <p>{{ $store->name }} 様</p>
        
        <p>いつもお世話になっております。<br>
        この度は、下記見積もり案件の営業権を獲得されましたことをお知らせいたします。</p>
        
        <div class="highlight">
            <h3>📋 見積もり案件情報</h3>
            <table class="table">
                <tr>
                    <th>お客様名</th>
                    <td>{{ $estimate->name }} 様</td>
                </tr>
                <tr>
                    <th>引越し元</th>
                    <td>{{ $estimate->movingFromAddress->prefecture ?? '' }} {{ $estimate->movingFromAddress->street_address ?? '' }}</td>
                </tr>
                <tr>
                    <th>引越し先</th>
                    <td>{{ $estimate->movingToAddress->prefecture ?? '' }} {{ $estimate->movingToAddress->street_address ?? '' }}</td>
                </tr>
                <tr>
                    <th>引越し予定日</th>
                    <td>
                        @if($estimate->moving_date_type === 'decided' && $estimate->moving_specific_date)
                            {{ \Carbon\Carbon::parse($estimate->moving_specific_date)->format('Y年n月j日') }}
                        @else
                            {{ $estimate->moving_year_month ?? '' }} {{ $estimate->moving_period ?? '' }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>人数</th>
                    <td>{{ $estimate->people_count }}人</td>
                </tr>
            </table>
        </div>
        
        <div class="highlight">
            <h3>🎯 あなたの入札結果</h3>
            <p><span class="ranking">第{{ $ranking }}位</span> での営業権獲得です！</p>
            <p>入札金額: <span class="amount">{{ $bidAmountMin }}円 ～ {{ $bidAmountMax }}円</span></p>
        </div>
        
        @if(count($allWinners) > 1)
        <div class="highlight">
            <h3>🏅 営業権獲得業者一覧</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>順位</th>
                        <th>業者名</th>
                        <th>入札金額</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allWinners as $winner)
                    <tr style="{{ $winner['store_id'] === $store->id ? 'background-color: #fff3cd;' : '' }}">
                        <td>第{{ $winner['ranking'] }}位</td>
                        <td>
                            {{ $winner['store_name'] }}
                            @if($winner['store_id'] === $store->id)
                                <strong>（あなた）</strong>
                            @endif
                        </td>
                        <td>{{ number_format($winner['bid_amount_min']) }}円 ～ {{ number_format($winner['bid_amount_max']) }}円</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        
        <div class="highlight">
            <h3>📞 次のステップ</h3>
            <p>営業権を獲得されましたので、お客様に直接ご連絡いただけます。</p>
            <p><strong>お客様連絡先:</strong><br>
            📧 {{ $estimate->email }}<br>
            📱 {{ $estimate->phone }}</p>
            
            <p><strong>⚠️ ご注意事項:</strong></p>
            <ul>
                <li>お客様への初回連絡は、営業権獲得から48時間以内にお願いします</li>
                <li>丁寧な対応を心がけ、お客様にご満足いただけるサービスを提供してください</li>
                <li>価格交渉は入札金額の範囲内でお願いします</li>
            </ul>
        </div>
        
        <p>この度は営業権を獲得いただき、ありがとうございました。<br>
        お客様にとって最適な引越しサービスを提供していただけますよう、よろしくお願いいたします。</p>
        
        <div class="footer">
            <p>このメールは自動送信されています。<br>
            ご不明な点がございましたら、サポートセンターまでお問い合わせください。</p>
        </div>
    </div>
</body>
</html>
