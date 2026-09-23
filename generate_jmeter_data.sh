#!/bin/bash

# إنشاء ملف بيانات الشركات (company_data.csv)
echo "company_name,responsible_name,email,phone,commercial_register" > company_data.csv
for i in {1..100}; do
    echo "Amazing Company $i,Mohammed Ahmad $i,company_test_$i@diems.com,0934936$(printf "%03d" $i),CR9988$i" >> company_data.csv
done
echo "✅ تم إنشاء company_data.csv"

# إنشاء ملف بيانات الفعاليات (event_data.csv)
echo "slot_id,organizer_name,organizer_email,organizer_phone,event_title" > event_data.csv
for i in {1..100}; do
    # نفترض أن لديك Slots متاحة بأرقام من 1 إلى 100
    echo "$i,Organizer $i,organizer_$i@diems.com,0944936$(printf "%03d" $i),Event Title $i" >> event_data.csv
done
echo "✅ تم إنشاء event_data.csv"

# إنشاء ملف بيانات الزوار والتذاكر (ticket_data.csv)
echo "visitor_name,visitor_email,visitor_phone" > ticket_data.csv
for i in {1..100}; do
    echo "Rawan $i,rawan_$i@diems.com,098877$(printf "%04d" $i)" >> ticket_data.csv
done
echo "✅ تم إنشاء ticket_data.csv"
