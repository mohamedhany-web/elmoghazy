<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            [
                'slug' => 'prep',
                'title' => 'المرحلة الإعدادية',
                'subtitle' => 'أساسيات الحركة والطاقة',
                'lessons' => 24,
                'accent' => 'cyan',
                'sort' => 1,
                'courses' => [
                    ['slug' => 'prep-1', 'title' => 'الحركة والقوى', 'lessons' => 8, 'description' => 'السرعة والتسارع وقوانين نيوتن بأسلوب مبسّط.', 'tag' => 'أساسي', 'mode' => 'recorded', 'duration' => 'الترم كامل', 'price' => 350],
                    ['slug' => 'prep-2', 'title' => 'الطاقة والشغل', 'lessons' => 8, 'description' => 'أنواع الطاقة وتحويلاتها وتمارين عملية.', 'tag' => 'أساسي', 'mode' => 'online', 'duration' => 'الترم كامل', 'price' => 350],
                    ['slug' => 'prep-3', 'title' => 'الضغط والموائع', 'lessons' => 8, 'description' => 'ضغط السوائل والغازات وتطبيقات يومية.', 'tag' => 'مراجعة', 'mode' => 'live', 'duration' => 'شهر', 'price' => 200],
                ],
            ],
            [
                'slug' => '1st',
                'title' => 'الصف الأول الثانوي',
                'subtitle' => 'الشغل والطاقة والميكانيكا',
                'lessons' => 32,
                'accent' => 'amber',
                'sort' => 2,
                'courses' => [
                    ['slug' => '1st-1', 'title' => 'الميكانيكا — الحركة', 'lessons' => 10, 'description' => 'الحركة في خط مستقيم والرسوم البيانية.', 'tag' => 'ترم أول', 'mode' => 'recorded', 'duration' => 'الترم كامل', 'price' => 500],
                    ['slug' => '1st-2', 'title' => 'الشغل والطاقة', 'lessons' => 12, 'description' => 'الشغل وطاقة الحركة والجهد وقانون الحفظ.', 'tag' => 'ترم أول', 'mode' => 'online', 'duration' => 'الترم كامل', 'price' => 500],
                    ['slug' => '1st-3', 'title' => 'المرونة والاهتزاز', 'lessons' => 10, 'description' => 'قانون هوك والحركة التوافقية البسيطة.', 'tag' => 'ترم تاني', 'mode' => 'live', 'duration' => 'شهرين', 'price' => 450],
                ],
            ],
            [
                'slug' => '2nd',
                'title' => 'الصف الثاني الثانوي',
                'subtitle' => 'الكهرباء والتيار والمقاومة',
                'lessons' => 36,
                'accent' => 'emerald',
                'sort' => 3,
                'courses' => [
                    ['slug' => '2nd-1', 'title' => 'الكهروستاتيكا', 'lessons' => 12, 'description' => 'الشحنة والمجال والجهد بأفكار واضحة.', 'tag' => 'ترم أول', 'mode' => 'recorded', 'duration' => 'الترم كامل', 'price' => 550],
                    ['slug' => '2nd-2', 'title' => 'التيار والمقاومة', 'lessons' => 12, 'description' => 'قانون أوم والدوائر على التوالي والتوازي.', 'tag' => 'أساسي', 'mode' => 'online', 'duration' => 'الترم كامل', 'price' => 550],
                    ['slug' => '2nd-3', 'title' => 'المغناطيسية والكهرومغناطيسية', 'lessons' => 12, 'description' => 'المجال المغناطيسي والقوة على موصل.', 'tag' => 'ترم تاني', 'mode' => 'live', 'duration' => 'شهرين', 'price' => 500],
                ],
            ],
            [
                'slug' => '3rd',
                'title' => 'الصف الثالث الثانوي',
                'subtitle' => 'الموجات والضوء والمراجعة',
                'lessons' => 40,
                'accent' => 'violet',
                'sort' => 4,
                'courses' => [
                    ['slug' => '3rd-1', 'title' => 'الموجات الميكانيكية', 'lessons' => 12, 'description' => 'أنواع الموجات والخصائص والتمارين.', 'tag' => 'ترم أول', 'mode' => 'recorded', 'duration' => 'الترم كامل', 'price' => 650],
                    ['slug' => '3rd-2', 'title' => 'الضوء والبصريات', 'lessons' => 14, 'description' => 'الانعكاس والانكسار والعدسات.', 'tag' => 'أساسي', 'mode' => 'online', 'duration' => 'الترم كامل', 'price' => 650],
                    ['slug' => '3rd-3', 'title' => 'مراجعة نهائية وبنوك أسئلة', 'lessons' => 14, 'description' => 'تجميعات امتحانات وحلول نموذجية.', 'tag' => 'قبل الامتحان', 'mode' => 'live', 'duration' => 'شهرين', 'price' => 400],
                ],
            ],
        ];

        foreach ($grades as $gradeData) {
            $courses = $gradeData['courses'];
            unset($gradeData['courses']);

            $grade = Grade::updateOrCreate(
                ['slug' => $gradeData['slug']],
                $gradeData,
            );

            foreach ($courses as $index => $course) {
                Course::updateOrCreate(
                    ['slug' => $course['slug']],
                    [
                        ...$course,
                        'grade_id' => $grade->id,
                        'sort' => $index + 1,
                    ],
                );
            }
        }
    }
}
