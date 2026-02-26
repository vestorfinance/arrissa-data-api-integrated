OK I wnat to create what is called tool managenet protocol TMP

FFIRST i WOULD LIKE US TO MAKE A DATABASE OF ALL AVABLIE POSSIBLE ACTIONS FROM ALL OUR APIS

firtly we must have a adatabse with tools stored in this format

tool_categories table
categories: market-data,chart-images,economic-calendar,orders,market-analysis (tma,quaters-theory)

tools:
you must define all possible tools based on the api files red the api files completely and pu all capabilities example 1
1. tool_name: get market data using count tool_format: {base_url}/market-data-api-v1/market-data-api.php?api_key={must-automatically-put api key here}&symbol={symbol}&timeframe={timeframe}&count={count}" inputs_explanation: symbol=symbol, timeframe=M1,M5,M15(FILL ALL AS API REQUIRES), count=number description: This tool gets specified number ofcandles for a scpcified timegrame and an instrument search_phrase: get number of candles (we nmust have only one unique phrase for each tool that describes what that tool does )  

considering the rest of ur apis i wnat you to creat all possible tool collection beased on the api files read them and make avalable all tools before we create tha databse lest make a text tile with all tools categoirse etc lets make the colelction based on the api files
