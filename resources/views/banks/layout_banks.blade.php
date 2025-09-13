<table style="border: 1px solid #CECECE; width: 100% !important" border="0">
	@foreach ($data as $client)
	<tr>
		<td style="width: 90% !important">{{ $client->name }}</td>
		<td style="width: 10% !important; text-align: center !important"><i class="fa fa-check-circle-o text-success" aria-hidden="true"></i></td>
	</tr>
	@endforeach
</table>
