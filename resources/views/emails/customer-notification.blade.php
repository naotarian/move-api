<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>営業権獲得業者のご案内</title>
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
        .success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
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
        .rank-1 { background-color: #fff3cd; }
        .rank-2 { background-color: #f8f9fa; }
        .rank-3 { background-color: #f8f9fa; }
        .amount {
            font-weight: bold;
            color: #e74c3c;
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
        <h1>📞 営業権獲得業者のご案内</h1>
    </div>
    
    <div class="content">
        <p>{{ $estimate->name }} 様</p>
        
        <p>この度は、引越し見積もりサービスをご利用いただき、誠にありがとうございます。</p>
        
        <div class="success">
            <h3>🎉 入札が完了しました！</h3>
            <p>あなたの引越し見積もりに対して、<strong>{{ $winnerCount }}社</strong>の優良業者が営業権を獲得いたしました。<br>
            最安値は <strong class="amount">{{ $topBidAmount }}円</strong> からとなっております。</p>
        </div>
        
        <div class="highlight">
            <h3>📋 あなたの引越し情報</h3>
            <table class="table">
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
            <h3>🏅 営業権獲得業者一覧</h3>
            <p>以下の業者があなたの引越しの営業権を獲得しました。近日中に各業者から直接ご連絡がございます。</p>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>順位</th>
                        <th>業者名</th>
                        <th>見積もり金額（目安）</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($businessRights as $index => $right)
                    <tr class="rank-{{ min($right['ranking'], 3) }}">
                        <td>
                            @if($right['ranking'] == 1)
                                🥇 第{{ $right['ranking'] }}位
                            @elseif($right['ranking'] == 2)
                                🥈 第{{ $right['ranking'] }}位
                            @elseif($right['ranking'] == 3)
                                🥉 第{{ $right['ranking'] }}位
                            @else
                                第{{ $right['ranking'] }}位
                            @endif
                        </td>
                        <td><strong>{{ $right['store_name'] }}</strong></td>
                        <td class="amount">{{ number_format($right['bid_amount_min']) }}円 ～ {{ number_format($right['bid_amount_max']) }}円</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="highlight">
            <h3>📞 これからの流れ</h3>
            <ol>
                <li><strong>業者からの連絡待ち</strong><br>
                    営業権を獲得した業者から48時間以内にご連絡があります。</li>
                <li><strong>詳細見積もりの取得</strong><br>
                    各業者と詳細な打ち合わせを行い、正確な見積もりを取得してください。</li>
                <li><strong>業者の比較・選定</strong><br>
                    価格だけでなく、サービス内容や対応も含めて総合的に判断してください。</li>
                <li><strong>契約・引越し実行</strong><br>
                    最適な業者を選んで契約し、安心して引越しを行ってください。</li>
            </ol>
        </div>
        
        <div class="highlight">
            <h3>💡 お役立ち情報</h3>
            <ul>
                <li><strong>価格交渉のコツ:</strong> 複数社から連絡があることを伝えると、より良い条件を提示してもらえる可能性があります。</li>
                <li><strong>サービス内容の確認:</strong> 梱包・開梱サービス、不用品回収、保険などのオプションサービスも確認しましょう。</li>
                <li><strong>口コミ・評判:</strong> 各業者の過去の実績や口コミも参考にしてください。</li>
            </ul>
        </div>
        
        <p>素敵な新生活のスタートを心よりお祈り申し上げます。<br>
        ご不明な点がございましたら、いつでもお気軽にお問い合わせください。</p>
        
        <div class="footer">
            <p>このメールは自動送信されています。<br>
            ご質問やお困りのことがございましたら、サポートセンターまでお問い合わせください。<br>
            📧 support@moving-auction.local | 📞 0120-123-456</p>
        </div>
    </div>
</body>
</html>
